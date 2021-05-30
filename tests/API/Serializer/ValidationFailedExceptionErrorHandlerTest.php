<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Serializer;

use App\API\Serializer\ValidationFailedExceptionErrorHandler;
use App\Validator\ValidationFailedException;
use FOS\RestBundle\Serializer\Normalizer\FlattenExceptionHandler;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\API\Serializer\ValidationFailedExceptionErrorHandler
 */
class ValidationFailedExceptionErrorHandlerTest extends TestCase
{
    public function testSubscribingMethods()
    {
        self::assertEquals([[
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'type' => FlattenException::class,
            'format' => 'json',
            'method' => 'serializeExceptionToJson',
            'priority' => -1,
        ], [
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'type' => ValidationFailedException::class,
            'format' => 'json',
            'method' => 'serializeValidationExceptionToJson',
            'priority' => -1,
        ]], ValidationFailedExceptionErrorHandler::getSubscribingMethods());
    }

    public function testWithEmptyConstraintsList()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $handler = $this->createMock(FlattenExceptionHandler::class);
        $sut = new ValidationFailedExceptionErrorHandler($translator, $handler);

        $constraints = new ConstraintViolationList();
        $validations = new ValidationFailedException($constraints, 'Uuups, that is broken');

        $expected = [
            'code' => '400',
            'message' => null,
            'errors' => [
                'children' => [],
            ],
        ];
        self::assertEquals($expected, $sut->serializeValidationExceptionToJson(new JsonSerializationVisitor(), $validations, [], new SerializationContext()));
    }

    public function testWithUnsupportedException()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $handler = $this->createMock(FlattenExceptionHandler::class);
        $handler->method('serializeToJson')->willReturn('foooo');
        $sut = new ValidationFailedExceptionErrorHandler($translator, $handler);
        $actual = $sut->serializeExceptionToJson(
            new JsonSerializationVisitor(),
            FlattenException::createFromThrowable(new \Exception('sdfsdf')),
            [],
            new SerializationContext()
        );

        self::assertEquals('foooo', $actual);
    }

    public function testWithConstraintsList()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $handler = $this->createMock(FlattenExceptionHandler::class);
        $sut = new ValidationFailedExceptionErrorHandler($translator, $handler);
        $translator->method('trans')->willReturnArgument(0);

        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));
        $constraints->add(new ConstraintViolation('missing tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));
        $constraints->add(new ConstraintViolation('missing tests', 'test %wuuf% 123', ['%wuuf%' => 'xcv'], '$root', 'end', 4, 3, null, null, '$cause'));
        $validations = new ValidationFailedException($constraints, 'Uuups, that is broken');

        $context = new SerializationContext();
        $context->setAttribute('exception', $validations);

        $expected = [
            'code' => '400',
            'message' => 'Uuups, that is broken',
            'errors' => [
                'children' => [
                    'begin' => [
                        'errors' => [
                            0 => 'abc.def',
                            1 => 'abc.def',
                        ],
                    ],
                    'end' => [
                        'errors' => [
                            0 => 'test %wuuf% 123',
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $sut->serializeExceptionToJson(
            new JsonSerializationVisitor(),
            FlattenException::createFromThrowable($validations),
            [],
            $context
        ));
    }

    public function testWithConstraintsListAndWrongException()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $handler = $this->createMock(FlattenExceptionHandler::class);
        $handler->method('serializeToJson')->willReturn('foooo');
        $sut = new ValidationFailedExceptionErrorHandler($translator, $handler);
        $translator->method('trans')->willReturnArgument(0);

        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));
        $constraints->add(new ConstraintViolation('missing tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));
        $constraints->add(new ConstraintViolation('missing tests', 'test %wuuf% 123', ['%wuuf%' => 'xcv'], '$root', 'end', 4, 3, null, null, '$cause'));
        $validations = new ValidationFailedException($constraints, 'Uuups, that is broken');

        $context = new SerializationContext();
        $context->setAttribute('exception', new \Exception('sdfsdf'));

        self::assertEquals('foooo', $sut->serializeExceptionToJson(
            new JsonSerializationVisitor(),
            FlattenException::createFromThrowable($validations),
            [],
            $context
        ));
    }
}
