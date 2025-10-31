<?php

declare(strict_types=1);

namespace Tourze\AsyncServiceCallBundle\MessageHandler;

use Monolog\Attribute\WithMonologChannel;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Tourze\AsyncServiceCallBundle\Exception\MethodNotFoundException;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;
use Tourze\AsyncServiceCallBundle\Service\Serializer;

#[AsMessageHandler]
#[WithMonologChannel(channel: 'async_service_call')]
readonly class ServiceCallHandler
{
    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private Serializer $serializer,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ServiceCallMessage $message): void
    {
        try {
            $method = $message->getMethod();
            // 在这里我们收到的数据不是原始数据，需要反序列化一次
            $params = $this->serializer->decodeParams($message->getParams());

            $service = $this->container->get($message->getServiceId());
            if (method_exists($service, $method)) {
                $reflection = new \ReflectionMethod($service, $method);
                $reflection->invokeArgs($service, $params);
            } else {
                throw new MethodNotFoundException(sprintf('Method %s does not exist on service %s', $method, $message->getServiceId()));
            }
        } catch (\Throwable $exception) {
            $this->logger->error('异步调用服务方法失败:' . $exception->getMessage(), [
                'exception' => $exception,
                'message' => $message,
                'retryCount' => $message->getRetryCount(),
            ]);

            // 如果还有重试次数，那我们就要尝试重新投递
            if ($message->getRetryCount() > 0) {
                $newMessage = clone $message;
                $newMessage->setRetryCount($message->getRetryCount() - 1); // 减1次

                // 指数退避：重试次数越多，延迟越长
                $attempt = $newMessage->getMaxRetryCount() - $newMessage->getRetryCount() + 1;
                $delaySecond = min(pow(2, $attempt), 60 * 60);
                $this->messageBus->dispatch($newMessage, [
                    new DelayStamp((int) ($delaySecond * 1000)),
                ]);
            }
        }
    }
}
