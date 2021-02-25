<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class ExceptionSubscriberTest extends TestCase
{
    public function testEventSubscription()
    {
        $this->assertArrayHasKey(ExceptionEvent::class, ExceptionSubscriber::getSubscribedEvents());
    }

    public function testOnExceptionSendEmail()
    {
        $mailer = $this->getMockBuilder(Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailer->expects($this->once())->method('send');
        $this->dispatch($mailer);

    }

    public function testOnExceptionSendEmailToTheAdmin()
    {
        $mailer = $this->getMockBuilder(Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailer->expects($this->once())->method('send')->with(
            $this->callback(function(Swift_Message $message) {
                return 
                    array_key_exists('from@domain.fr', $message->getFrom()) &&
                    array_key_exists('to@domain.fr', $message->getTo());
            })
        );
        $this->dispatch($mailer);
    }

    public function testOnExceptionSendEmailWithTrace()
    {
        $mailer = $this->getMockBuilder(Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailer->expects($this->once())->method('send')->with(
            $this->callback(function(Swift_Message $message) {
                return 
                    strpos($message->getBody(), 'ExceptionSubscriberTest') &&
                    strpos($message->getBody(), 'Hello world');
            })
        );
        $this->dispatch($mailer);
    }

    private function dispatch($mailer)
    {
        $subscriber = new ExceptionSubscriber($mailer, 'from@doamin.fr', 'to@domain.fr');
        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $event = new ExceptionEvent($kernel, new Request(), 1, new \Exception('Hello world'));
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event);

    }
}
