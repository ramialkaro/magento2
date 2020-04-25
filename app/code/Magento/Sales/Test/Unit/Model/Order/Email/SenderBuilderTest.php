<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Test\Unit\Model\Order\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\ShipmentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\SenderBuilder;
use Magento\Sales\Test\Unit\Model\Order\Email\Stub\TransportInterfaceMock;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SenderBuilderTest extends TestCase
{
    /**
     * @var SenderBuilder
     */
    protected $senderBuilder;

    /**
     * @var MockObject
     */
    protected $templateContainerMock;

    /**
     * @var MockObject
     */
    protected $identityContainerMock;

    /**
     * @var MockObject
     */
    protected $transportBuilder;

    /**
     * @var MockObject
     */
    private $storeMock;

    protected function setUp(): void
    {
        $this->templateContainerMock = $this->createPartialMock(
            Template::class,
            ['getTemplateVars', 'getTemplateOptions', 'getTemplateId']
        );

        $this->storeMock = $this->createPartialMock(
            Store::class,
            [
                'getStoreId',
                '__wakeup',
                'getId',
            ]
        );

        $this->identityContainerMock = $this->createPartialMock(
            ShipmentIdentity::class,
            [
                'getEmailIdentity',
                'getCustomerEmail',
                'getCustomerName',
                'getTemplateOptions',
                'getEmailCopyTo',
                'getCopyMethod',
                'getStore',
            ]
        );

        $this->transportBuilder = $this->createPartialMock(
            TransportBuilder::class,
            [
                'addTo',
                'addBcc',
                'getTransport',
                'setTemplateIdentifier',
                'setTemplateOptions',
                'setTemplateVars',
                'setFromByScope',
            ]
        );

        $this->senderBuilder = new SenderBuilder(
            $this->templateContainerMock,
            $this->identityContainerMock,
            $this->transportBuilder
        );
    }

    public function testSend()
    {
        $this->setExpectedCount(1);
        $customerName = 'test_name';
        $customerEmail = 'test_email';
        $identity = 'email_identity_test';

        $transportMock = $this->createMock(
            TransportInterfaceMock::class
        );

        $this->identityContainerMock->expects($this->once())
            ->method('getEmailCopyTo')
            ->will($this->returnValue(['example@mail.com']));
        $this->identityContainerMock->expects($this->once())
            ->method('getCopyMethod')
            ->will($this->returnValue('bcc'));
        $this->identityContainerMock->expects($this->once())
            ->method('getCustomerEmail')
            ->will($this->returnValue($customerEmail));
        $this->identityContainerMock->expects($this->once())
            ->method('getCustomerName')
            ->will($this->returnValue($customerName));
        $this->identityContainerMock->expects($this->exactly(1))
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->transportBuilder->expects($this->exactly(1))
            ->method('setFromByScope')
            ->with($identity, 1);
        $this->transportBuilder->expects($this->exactly(1))
            ->method('addTo')
            ->with($this->equalTo($customerEmail), $this->equalTo($customerName));

        $this->transportBuilder->expects($this->exactly(1))
            ->method('getTransport')
            ->will($this->returnValue($transportMock));

        $this->senderBuilder->send();
    }

    public function testSendCopyTo()
    {
        $this->setExpectedCount(2);
        $identity = 'email_identity_test';
        $transportMock = $this->createMock(
            TransportInterfaceMock::class
        );
        $this->identityContainerMock->expects($this->never())
            ->method('getCustomerEmail');
        $this->identityContainerMock->expects($this->never())
            ->method('getCustomerName');
        $this->transportBuilder->expects($this->exactly(2))
            ->method('addTo');
        $this->transportBuilder->expects($this->exactly(2))
            ->method('setFromByScope')
            ->with($identity, 1);
        $this->identityContainerMock->expects($this->exactly(2))
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);
        $this->transportBuilder->expects($this->exactly(2))
            ->method('getTransport')
            ->will($this->returnValue($transportMock));

        $this->senderBuilder->sendCopyTo();
    }

    /**
     * Sets expected count invocation.
     *
     * @param int $count
     */
    private function setExpectedCount(int $count = 1)
    {
        $templateId = 'test_template_id';
        $templateOptions = ['option1', 'option2'];
        $templateVars = ['var1', 'var2'];
        $emailIdentity = 'email_identity_test';
        $emailCopyTo = ['example@mail.com', 'example2@mail.com'];

        $this->templateContainerMock->expects($this->exactly($count))
            ->method('getTemplateId')
            ->will($this->returnValue($templateId));
        $this->transportBuilder->expects($this->exactly($count))
            ->method('setTemplateIdentifier')
            ->with($this->equalTo($templateId));
        $this->templateContainerMock->expects($this->exactly($count))
            ->method('getTemplateOptions')
            ->will($this->returnValue($templateOptions));
        $this->transportBuilder->expects($this->exactly($count))
            ->method('setTemplateOptions')
            ->with($this->equalTo($templateOptions));
        $this->templateContainerMock->expects($this->exactly($count))
            ->method('getTemplateVars')
            ->will($this->returnValue($templateVars));
        $this->transportBuilder->expects($this->exactly($count))
            ->method('setTemplateVars')
            ->with($this->equalTo($templateVars));

        $this->identityContainerMock->expects($this->exactly($count))
            ->method('getEmailIdentity')
            ->will($this->returnValue($emailIdentity));
        $this->transportBuilder->expects($this->exactly($count))
            ->method('setFromByScope')
            ->with($this->equalTo($emailIdentity), 1);

        $this->identityContainerMock->expects($this->once())
            ->method('getEmailCopyTo')
            ->will($this->returnValue($emailCopyTo));
    }
}
