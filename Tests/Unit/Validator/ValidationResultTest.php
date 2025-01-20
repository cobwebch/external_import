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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the ValidationResult class.
 */
class ValidationResultTest extends UnitTestCase
{
    protected ValidationResult $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new ValidationResult();
    }

    #[Test]
    public function getAllInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getAll()
        );
    }

    #[Test]
    public function resetSetsResultsToEmptyArray(): void
    {
        $this->subject->add('foo', 'This is a validation result', ContextualFeedbackSeverity::NOTICE);
        $this->subject->reset();
        self::assertSame(
            [],
            $this->subject->getAll()
        );
    }

    #[Test]
    public function getForPropertyInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getForProperty('foo')
        );
    }

    #[Test]
    public function getForSeverityInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->subject->getForSeverity(ContextualFeedbackSeverity::NOTICE)
        );
    }

    /**
     * Returns an array of validation messages which forms the basis of several tests.
     *
     * @return \array[][][]
     */
    public static function getSampleMessages(): array
    {
        return [
            'single message' => [
                'messages' => [
                    [
                        'property' => 'foo',
                        'message' => 'This is a validation result',
                        'severity' => ContextualFeedbackSeverity::NOTICE,
                    ],
                ],
            ],
            'single message, not requested property, not requested severity' => [
                'messages' => [
                    [
                        'property' => 'baz',
                        'message' => 'This is a baz validation result',
                        'severity' => ContextualFeedbackSeverity::ERROR,
                    ],
                ],
            ],
            'two messages, same property, same severity' => [
                'messages' => [
                    [
                        'property' => 'foo',
                        'message' => 'This is a validation result',
                        'severity' => ContextualFeedbackSeverity::NOTICE,
                    ],
                    [
                        'property' => 'foo',
                        'message' => 'This is a second validation result',
                        'severity' => ContextualFeedbackSeverity::NOTICE,
                    ],
                ],
            ],
            'two messages, same property, different severity' => [
                'messages' => [
                    [
                        'property' => 'foo',
                        'message' => 'This is a notice validation result',
                        'severity' => ContextualFeedbackSeverity::NOTICE,
                    ],
                    [
                        'property' => 'foo',
                        'message' => 'This is a warning validation result',
                        'severity' => ContextualFeedbackSeverity::WARNING,
                    ],
                ],
            ],
            'two messages, different property, same severity' => [
                'messages' => [
                    [
                        'property' => 'foo',
                        'message' => 'This is a foo validation result',
                        'severity' => ContextualFeedbackSeverity::NOTICE,
                    ],
                    [
                        'property' => 'bar',
                        'message' => 'This is a bar validation result',
                        'severity' => ContextualFeedbackSeverity::NOTICE,
                    ],
                ],
            ],
        ];
    }

    public static function allResultsProvider(): array
    {
        return array_merge_recursive(
            self::getSampleMessages(),
            [
                'single message' => [
                    'expectedStructure' => [
                        'foo' => [
                            [
                                'severity' => ContextualFeedbackSeverity::NOTICE,
                                'message' => 'This is a validation result',
                            ],
                        ],
                    ],
                ],
                'single message, not requested property, not requested severity' => [
                    'expectedStructure' => [
                        'baz' => [
                            [
                                'severity' => ContextualFeedbackSeverity::ERROR,
                                'message' => 'This is a baz validation result',
                            ],
                        ],
                    ],
                ],
                'two messages, same property, same severity' => [
                    'expectedStructure' => [
                        'foo' => [
                            [
                                'severity' => ContextualFeedbackSeverity::NOTICE,
                                'message' => 'This is a validation result',
                            ],
                            [
                                'severity' => ContextualFeedbackSeverity::NOTICE,
                                'message' => 'This is a second validation result',
                            ],
                        ],
                    ],
                ],
                'two messages, same property, different severity' => [
                    'expectedStructure' => [
                        'foo' => [
                            [
                                'severity' => ContextualFeedbackSeverity::WARNING,
                                'message' => 'This is a warning validation result',
                            ],
                            [
                                'severity' => ContextualFeedbackSeverity::NOTICE,
                                'message' => 'This is a notice validation result',
                            ],
                        ],
                    ],
                ],
                'two messages, different property, same severity' => [
                    'expectedStructure' => [
                        'foo' => [
                            [
                                'severity' => ContextualFeedbackSeverity::NOTICE,
                                'message' => 'This is a foo validation result',
                            ],
                        ],
                        'bar' => [
                            [
                                'severity' => ContextualFeedbackSeverity::NOTICE,
                                'message' => 'This is a bar validation result',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    #[Test] #[DataProvider('allResultsProvider')]
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
     */
    #[Test] #[DataProvider('allResultsProvider')]
    public function getAllReturnsAllMessages(array $messages, array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
            $expectedStructure,
            $this->subject->getAll()
        );
    }

    public static function forPropertyProvider(): array
    {
        return array_merge_recursive(
            self::getSampleMessages(),
            [
                'single message' => [
                    'property' => 'foo',
                    'expectedStructure' => [
                        [
                            'severity' => ContextualFeedbackSeverity::NOTICE,
                            'message' => 'This is a validation result',
                        ],
                    ],
                ],
                'single message, not requested property, not requested severity' => [
                    'property' => 'foo',
                    'expectedStructure' => null,
                ],
                'two messages, same property, same severity' => [
                    'property' => 'foo',
                    'expectedStructure' => [
                        [
                            'severity' => ContextualFeedbackSeverity::NOTICE,
                            'message' => 'This is a validation result',
                        ],
                        [
                            'severity' => ContextualFeedbackSeverity::NOTICE,
                            'message' => 'This is a second validation result',
                        ],
                    ],
                ],
                'two messages, same property, different severity' => [
                    'property' => 'foo',
                    'expectedStructure' => [
                        [
                            'severity' => ContextualFeedbackSeverity::WARNING,
                            'message' => 'This is a warning validation result',
                        ],
                        [
                            'severity' => ContextualFeedbackSeverity::NOTICE,
                            'message' => 'This is a notice validation result',
                        ],
                    ],
                ],
                'two messages, different property, same severity' => [
                    'property' => 'foo',
                    'expectedStructure' => [
                        [
                            'severity' => ContextualFeedbackSeverity::NOTICE,
                            'message' => 'This is a foo validation result',
                        ],
                    ],
                ],
            ]
        );
    }

    #[Test] #[DataProvider('forPropertyProvider')]
    public function getForPropertyReturnsAllMessagesForProperty(array $messages, string $property, ?array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
            $expectedStructure,
            $this->subject->getForProperty($property)
        );
    }

    public static function forSeverityProvider(): array
    {
        return array_merge_recursive(
            self::getSampleMessages(),
            [
                'single message' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedStructure' => [
                        'foo' => [
                            'This is a validation result',
                        ],
                    ],
                ],
                'single message, not requested property, not requested severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedStructure' => [
                        'baz' => [],
                    ],
                ],
                'two messages, same property, same severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedStructure' => [
                        'foo' => [
                            'This is a validation result',
                            'This is a second validation result',
                        ],
                    ],
                ],
                'two messages, same property, different severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedStructure' => [
                        'foo' => [
                            'This is a notice validation result',
                        ],
                    ],
                ],
                'two messages, different property, same severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedStructure' => [
                        'foo' => [
                            'This is a foo validation result',
                        ],
                        'bar' => [
                            'This is a bar validation result',
                        ],
                    ],
                ],
            ]
        );
    }

    #[Test] #[DataProvider('forSeverityProvider')]
    public function getForSeverityReturnsAllMessagesForSeverity(array $messages, ContextualFeedbackSeverity $severity, array $expectedStructure): void
    {
        $this->loadMessages($messages);
        self::assertSame(
            $expectedStructure,
            $this->subject->getForSeverity($severity)
        );
    }

    public static function countForPropertyProvider(): array
    {
        return array_merge_recursive(
            self::getSampleMessages(),
            [
                'single message' => [
                    'property' => 'foo',
                    'expectedTotal' => 1,
                ],
                'single message, not requested property, not requested severity' => [
                    'property' => 'foo',
                    'expectedTotal' => 0,
                ],
                'two messages, same property, same severity' => [
                    'property' => 'foo',
                    'expectedTotal' => 2,
                ],
                'two messages, same property, different severity' => [
                    'property' => 'foo',
                    'expectedTotal' => 2,
                ],
                'two messages, different property, same severity' => [
                    'property' => 'foo',
                    'expectedTotal' => 1,
                ],
            ]
        );
    }

    #[Test] #[DataProvider('countForPropertyProvider')]
    public function countForPropertyReturnsTotalMessagesForProperty(array $messages, string $property, int $expectedTotal): void
    {
        $this->loadMessages($messages);
        self::assertSame(
            $expectedTotal,
            $this->subject->countForProperty($property)
        );
    }

    public static function countForSeverityProvider(): array
    {
        return array_merge_recursive(
            self::getSampleMessages(),
            [
                'single message' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedTotal' => 1,
                ],
                'single message, not requested property, not requested severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedTotal' => 0,
                ],
                'two messages, same property, same severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedTotal' => 2,
                ],
                'two messages, same property, different severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedTotal' => 1,
                ],
                'two messages, different property, same severity' => [
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'expectedTotal' => 2,
                ],
            ]
        );
    }

    #[Test] #[DataProvider('countForSeverityProvider')]
    public function countForSeverityReturnsTotalMessagesForSeverity(array $messages, ContextualFeedbackSeverity $severity, int $expectedTotal): void
    {
        $this->loadMessages($messages);
        self::assertSame(
            $expectedTotal,
            $this->subject->countForSeverity($severity)
        );
    }

    public static function getForPropertyAndSeverityProvider(): array
    {
        return array_merge_recursive(
            self::getSampleMessages(),
            [
                'single message' => [
                    'property' => 'foo',
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'count' => 1,
                ],
                'single message, not requested property, not requested severity' => [
                    'property' => 'foo',
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'count' => 0,
                ],
                'two messages, same property, same severity' => [
                    'property' => 'foo',
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'count' => 2,
                ],
                'two messages, same property, different severity' => [
                    'property' => 'foo',
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'count' => 1,
                ],
                'two messages, different property, same severity' => [
                    'property' => 'foo',
                    'severity' => ContextualFeedbackSeverity::NOTICE,
                    'count' => 1,
                ],
            ]
        );
    }

    #[Test] #[DataProvider('getForPropertyAndSeverityProvider')]
    public function getForPropertyAndSeverityReturnsAllMessagesForPropertyAndSeverity(array $messages, string $property, ContextualFeedbackSeverity $severity, int $count): void
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
