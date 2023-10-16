<?php

namespace CR\OfficialCleverreach\Tests\Unit\Domain\Model;

use \TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 */
class ProcessTest extends UnitTestCase
{
    /**
     * @var \CR\OfficialCleverreach\Domain\Model\Process
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \CR\OfficialCleverreach\Domain\Model\Process();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getIdReturnsInitialValueForInt()
    {
    }

    /**
     * @test
     */
    public function setIdForIntSetsId()
    {
    }

    /**
     * @test
     */
    public function getRunnerReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getRunner()
        );
    }

    /**
     * @test
     */
    public function setRunnerForStringSetsRunner()
    {
        $this->subject->setRunner('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'runner',
            $this->subject
        );
    }
}
