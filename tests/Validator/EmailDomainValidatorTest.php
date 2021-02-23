<?php

namespace App\Tests\Validator;

use App\Repository\ConfigRepository;
use App\Validator\EmailDomain;
use App\Validator\EmailDomainValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

// use KernelTestCase only for testParameterSetCorrecty method
// would be better to extends TestCase only => test unitaire
// and add testParameterSetCorretly in another file => fonctional test
class EmailDomainValidatorTest extends KernelTestCase 
{
    private function getValidator($expectedViolation = false, $dbBlockedDomain = [])
    {
        $repository = $this->getMockBuilder(ConfigRepository::class)
            ->disableOriginalConstructor()
            ->getMock();


        $repository->expects($this->any())
            ->method('getAsArray')
            ->with('blocked_domain')
            ->willReturn($dbBlockedDomain);

        $validator = new EmailDomainValidator($repository);
        $context = $this->getContext($expectedViolation);

        $validator->initialize($context);
        return $validator;
    }

    private function getContext(bool $expectedViolation): ExecutionContextInterface
    {
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();

        if ($expectedViolation) {
            $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
            
            $violation->expects($this->any())->method('setParameter')->willReturn($violation);
            $violation->expects($this->once())->method('addViolation');
            $context->expects($this->once())->method('buildViolation')->willReturn($violation);
            
        } else {
            $context->expects($this->never())->method('buildViolation');
        }

        return $context;
    }

    public function testCatchBadDomains()
    {
        $constraint = new EmailDomain([
            'blocked' => ['baddomain.fr', 'aze.com']
        ]);

        $this->getValidator(true)->validate('demo@baddomain.fr', $constraint);
    }

    public function testAcceptGoodDomains()
    {
        $constraint = new EmailDomain([
            'blocked' => ['baddomain.fr', 'aze.com']
        ]);

        $this->getValidator(false)->validate('demo@gooddomain.fr', $constraint);
    }

    public function testBlockedDomainFromDatabase()
    {
        $constraint = new EmailDomain([
            'blocked' => ['baddomain.fr', 'aze.com']
        ]);

        $this->getValidator(true, ['baddomain.fr'])->validate('demo@baddomain.fr', $constraint);
    }

    public function testParameterSetCorrectly()
    {
        $constraint = new EmailDomain(['blocked' => []]);

        self::bootKernel();
        $validator = self::$container->get(EmailDomainValidator::class);
        $validator->initialize($this->getContext(true));
        $validator->validate('demo@globalblocked.fr', $constraint);
    }
}
