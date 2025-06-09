<?php

namespace Tourze\AsyncServiceCallBundle\MessageHandler;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;
use Tourze\AsyncServiceCallBundle\Service\Serializer;

#[AsMessageHandler]
class ServiceCallHandler
{
    public function __construct(
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
        private readonly Serializer $serializer,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
    )
    {
    }

    public function __invoke(ServiceCallMessage $message): void
    {
        try {
            $method = $message->getMethod();
            // 在这里我们收到的数据不是原始数据，需要反序列化一次
            $params = $this->serializer->decodeParams($message->getParams());

            $service = $this->container->get($message->getServiceId());
            call_user_func_array([$service, $method], $params);
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

                $delaySecond = min($newMessage->getMaxRetryCount() - $newMessage->getRetryCount(), 60 * 60);
                $this->messageBus->dispatch($newMessage, [
                    new DelayStamp($delaySecond * 1000),
                ]);
            }
        }
    }
}
