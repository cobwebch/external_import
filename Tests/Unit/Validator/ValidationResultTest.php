<?php

namespace Cobweb\ExternalImport\Tests\Unit\Validator;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\ExternalImport\Validator\ValidationResult;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * Test case for the Step utility.
 *
 * @package Cobweb\ExternalImport\Tests\Unit
 */
class ValidationResultTest extends UnitTestCase
{
    /**
     * @var ValidationResult
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new ValidationResult();
    }

    /**
     * @test
     */
    public function getAllInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getAll()
        );
    }

    /**
     * @test
     */
    public function getForPropertyInitiallyReturnsNull(): void
    {
        self::assertNull(
                $this->subject->getForProperty('foo')
        );
    }

    /**
     * @test
     */
    public function getForSeverityInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
                [],
                $this->subject->getForSeverity(AbstractMessage::NOTICE)
        );
    }

    /**
     * Returns an array of validation messages which forms the basis of several tests.
     *
     * @return \array[][][]
     */
    public function getSampleMessages(): array
    {
        return [
                'single message' => [
                        'messages' => [
                                [
                                        'property' => 'foo',
                                        'message' => 'This is a validation result',
                                        'severity' => AbstractMessage::NOTICE
                                ]
                        ]
                ],
                'single message, not requested property, not requested severity' => [
                        'messages' => [
                                [
                                        'property' => 'baz',
                                        'message' => 'This is a baz validation result',
                                        'severity' => AbstractMessage::ERROR
                                ]
                        ]
                ],
                'two messages, same property, same severity' => [
                        'messages' => [
                                [
                                        'property' => 'foo',
                                        'message' => 'This is a validation result',
                                        'severity' => AbstractMessage::NOTICE
                                ],
                                [
                                        'property' => 'foo',
                                        'message' => 'This is a second validation result',
                                        'severity' => AbstractMessage::NOTICE
                                ]
                        ]
                ],
                'two messages, same property, different severity' => [
                        'messages' => [
                                [
                                        'property' => 'foo',
                                        'message' => 'This is a notice validation result',
                                        'severity' => AbstractMessage::NOTICE
                                ],
                                [
                                        'property' => 'foo',
                                        'message' => 'This is a warning validation result',
                                        'severity' => AbstractMessage::WARNING
                                ]
                        ]
                ],
                'two messages, different property, same severity' => [
                        'messages' => [
                                [
                                        'property' => 'foo',
                                        'message' => 'This is a foo validation result',
                                        'severity' => AbstractMessage::NOTICE
                                ],
                                [
                                        'property' => 'bar',
                                        'message' => 'This is a bar validation result',
                                        'severity' => AbstractMessage::NOTICE
                                ]
                        ]
                ]
        ];
    }

    public function allResultsProvider(): array
    {
        return array_merge_recursive(
                $this->getSampleMessages(),
                [
                        'single message' => [
                                'expected' => [
                                        'foo' => [
                                                [
                                                        'severity' => AbstractMessage::NOTICE,
                                                        'message' => 'This is a validation result'
                                                ]
                                        ]
                                ]
                        ],
                        'single message, not requested property, not requested severity' => [
                                'expected' => [
                                        'baz' => [
                                                [
                                                        'severity' => AbstractMessage::ERROR,
                                                        'message' => 'This is a baz validation result'
                                                ]
                                        ]
                                ]
                        ],
                        'two messages, same property, same severity' => [
                                'expected' => [
                                        'foo' => [
                                                [
                                                        'severity' => AbstractMessage::NOTICE,
                                                        'message' => 'This is a validation result'
                                                ],
                                                [
                                                        'severity' => AbstractMessage::NOTICE,
                                                        'message' => 'This is a second validation result'
                                                ]
                                        ]
                                ]
                        ],
                        'two messages, same property, different severity' => [
                                'expected' => [
                                        'foo' => [
                                                [
                                                        'severity' => AbstractMessage::WARNING,
                                                        'message' => 'This is a warning validation result'
                                                ],
                                                [
                                                        'severity' => AbstractMessage::NOTICE,
                                                        'message' => 'This is a notice validation result'
                                                ]
                                        ]
                                ]
                        ],
                        'two messages, different property, same severity' => [
                                'expected' => [
                                        'foo' => [
                                                [
                                                        'severity' => AbstractMessage::NOTICE,
                                                        'message' => 'This is a foo validation result'
                                                ],
                                        ],
                                        'bar' => [
                                                [
                                                        'severity' => AbstractMessage::NOTICE,
                                                        'message' => 'This is a bar validation result'
                                                ]
                                        ]
                                ]
                        ]
                ]
        );
    }

    /**
     * @test
     * @dataProvider allResultsProvider
     * @param array $messages
     * @param array $expectedStructure
     */
    public function addAddsResultToList(array $messages, array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
                $expectedStructure,
                $this->subject->getAll()
        );
    }

    /**
     * This is currently the same as addAddsResultToList() above, but it was still separated
     * to clarify coverage and in case some variant is needed in the future.
     *
     * @test
     * @dataProvider allResultsProvider
     * @param array $messages
     * @param array $expectedStructure
     */
    public function getAllReturnsAllMessages(array $messages, array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
                $expectedStructure,
                $this->subject->getAll()
        );
    }

    public function forPropertyProvider(): array
    {
        return array_merge_recursive(
                $this->getSampleMessages(),
                [
                        'single message' => [
                                'property' => 'foo',
                                'expected' => [
                                        [
                                                'severity' => AbstractMessage::NOTICE,
                                                'message' => 'This is a validation result'
                                        ]
                                ]
                        ],
                        'single message, not requested property, not requested severity' => [
                                'property' => 'foo',
                                'expected' => null
                        ],
                        'two messages, same property, same severity' => [
                                'property' => 'foo',
                                'expected' => [
                                        [
                                                'severity' => AbstractMessage::NOTICE,
                                                'message' => 'This is a validation result'
                                        ],
                                        [
                                                'severity' => AbstractMessage::NOTICE,
                                                'message' => 'This is a second validation result'
                                        ]
                                ]
                        ],
                        'two messages, same property, different severity' => [
                                'property' => 'foo',
                                'expected' => [
                                        [
                                                'severity' => AbstractMessage::WARNING,
                                                'message' => 'This is a warning validation result'
                                        ],
                                        [
                                                'severity' => AbstractMessage::NOTICE,
                                                'message' => 'This is a notice validation result'
                                        ]
                                ]
                        ],
                        'two messages, different property, same severity' => [
                                'property' => 'foo',
                                'expected' => [
                                        [
                                                'severity' => AbstractMessage::NOTICE,
                                                'message' => 'This is a foo validation result'
                                        ]
                                ]
                        ]
                ]
        );
    }

    /**
     * @test
     * @dataProvider forPropertyProvider
     * @param array $messages
     * @param string $property
     * @param array|null $expectedStructure
     */
    public function getForPropertyReturnsAllMessagesForProperty(array $messages, string $property, ?array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
                $expectedStructure,
                $this->subject->getForProperty($property)
        );
    }

    public function forSeverityProvider(): array
    {
        return array_merge_recursive(
                $this->getSampleMessages(),
                [
                        'single message' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => [
                                        'foo' => [
                                                'This is a validation result'
                                        ]
                                ]
                        ],
                        'single message, not requested property, not requested severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => [
                                        'baz' => []
                                ]
                        ],
                        'two messages, same property, same severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => [
                                        'foo' => [
                                                'This is a validation result',
                                                'This is a second validation result'
                                        ]
                                ]
                        ],
                        'two messages, same property, different severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => [
                                        'foo' => [
                                                'This is a notice validation result'
                                        ]
                                ]
                        ],
                        'two messages, different property, same severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => [
                                        'foo' => [
                                                'This is a foo validation result'
                                        ],
                                        'bar' => [
                                                'This is a bar validation result'
                                        ]
                                ]
                        ]
                ]
        );
    }

    /**
     * @test
     * @dataProvider forSeverityProvider
     * @param array $messages
     * @param int $severity
     * @param array $expectedStructure
     */
    public function getForSeverityReturnsAllMessagesForSeverity(array $messages, int $severity, array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
                $expectedStructure,
                $this->subject->getForSeverity($severity)
        );
    }

    public function countForPropertyProvider(): array
    {
        return array_merge_recursive(
                $this->getSampleMessages(),
                [
                        'single message' => [
                                'property' => 'foo',
                                'expected' => 1
                        ],
                        'single message, not requested property, not requested severity' => [
                                'property' => 'foo',
                                'expected' => 0
                        ],
                        'two messages, same property, same severity' => [
                                'property' => 'foo',
                                'expected' => 2
                        ],
                        'two messages, same property, different severity' => [
                                'property' => 'foo',
                                'expected' => 2
                        ],
                        'two messages, different property, same severity' => [
                                'property' => 'foo',
                                'expected' => 1
                        ]
                ]
        );
    }

    /**
     * @test
     * @dataProvider countForPropertyProvider
     * @param array $messages
     * @param string $property
     * @param int $expectedTotal
     */
    public function countForPropertyReturnsTotalMessagesForProperty(array $messages, string $property, int $expectedTotal): void
    {
        $this->loadMessages($messages);
        self::assertSame(
                $expectedTotal,
                $this->subject->countForProperty($property)
        );
    }

    public function countForSeverityProvider(): array
    {
        return array_merge_recursive(
                $this->getSampleMessages(),
                [
                        'single message' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => 1
                        ],
                        'single message, not requested property, not requested severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => 0
                        ],
                        'two messages, same property, same severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => 2
                        ],
                        'two messages, same property, different severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => 1
                        ],
                        'two messages, different property, same severity' => [
                                'severity' => AbstractMessage::NOTICE,
                                'expected' => 2
                        ]
                ]
        );
    }

    /**
     * @test
     * @dataProvider countForSeverityProvider
     * @param array $messages
     * @param int $severity
     * @param int $expectedTotal
     */
    public function countForSeverityReturnsTotalMessagesForSeverity(array $messages, int $severity, int $expectedTotal): void
    {
        $this->loadMessages($messages);
        self::assertSame(
                $expectedTotal,
                $this->subject->countForSeverity($severity)
        );
    }

    public function getForPropertyAndSeverityProvider(): array
    {
        return array_merge_recursive(
                $this->getSampleMessages(),
                [
                        'single message' => [
                                'property' => 'foo',
                                'severity' => AbstractMessage::NOTICE,
                                'count' => 1
                        ],
                        'single message, not requested property, not requested severity' => [
                                'property' => 'foo',
                                'severity' => AbstractMessage::NOTICE,
                                'count' => 0
                        ],
                        'two messages, same property, same severity' => [
                                'property' => 'foo',
                                'severity' => AbstractMessage::NOTICE,
                                'count' => 2
                        ],
                        'two messages, same property, different severity' => [
                                'property' => 'foo',
                                'severity' => AbstractMessage::NOTICE,
                                'count' => 1
                        ],
                        'two messages, different property, same severity' => [
                                'property' => 'foo',
                                'severity' => AbstractMessage::NOTICE,
                                'count' => 1
                        ]
                ]
        );
    }

    /**
     * @test
     * @dataProvider getForPropertyAndSeverityProvider
     * @param array $messages
     * @param string $property
     * @param int $severity
     * @param int $count
     */
    public function getForPropertyAndSeverityReturnsAllMessagesForPropertyAndSeverity(array $messages, string $property, int $severity, int $count): void
    {
        $this->loadMessages($messages);
        self::assertCount(
                $count,
                $this->subject->getForPropertyAndSeverity($property, $severity)
        );
    }

    /**
     * Loads the standard messages into the test subject.
     *
     * @param array $messages
     */
    protected function loadMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->subject->add(
                    $message['property'],
                    $message['message'],
                    $message['severity']
            );
        }
    }
}