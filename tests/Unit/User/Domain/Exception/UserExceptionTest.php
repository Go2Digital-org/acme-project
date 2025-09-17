<?php

declare(strict_types=1);

use Modules\User\Domain\Exception\UserException;

describe('UserException', function (): void {
    it('creates userNotFound exception with id', function (): void {
        $userId = 123;
        $exception = UserException::userNotFound($userId);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("User with ID {$userId} not found");
    });

    it('creates userNotFoundByEmail exception', function (): void {
        $email = 'test@example.com';
        $exception = UserException::userNotFoundByEmail($email);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("User with email {$email} not found");
    });

    it('creates emailAlreadyExists exception', function (): void {
        $email = 'existing@example.com';
        $exception = UserException::emailAlreadyExists($email);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("User with email {$email} already exists");
    });

    it('creates cannotDeactivateAdmin exception', function (): void {
        $exception = UserException::cannotDeactivateAdmin();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Cannot deactivate administrator accounts');
    });

    it('creates cannotDeleteActiveUser exception', function (): void {
        $exception = UserException::cannotDeleteActiveUser();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Cannot delete active user account');
    });

    it('creates insufficientPermissions exception', function (): void {
        $action = 'delete_user';
        $exception = UserException::insufficientPermissions($action);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("Insufficient permissions to perform action: {$action}");
    });

    it('creates accountSuspended exception', function (): void {
        $exception = UserException::accountSuspended();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('User account has been suspended');
    });

    it('creates accountBlocked exception', function (): void {
        $exception = UserException::accountBlocked();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('User account has been permanently blocked');
    });

    it('creates accountNotVerified exception', function (): void {
        $exception = UserException::accountNotVerified();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('User account email has not been verified');
    });

    it('creates twoFactorNotEnabled exception', function (): void {
        $exception = UserException::twoFactorNotEnabled();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Two-factor authentication is not enabled for this user');
    });

    it('creates twoFactorAlreadyEnabled exception', function (): void {
        $exception = UserException::twoFactorAlreadyEnabled();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Two-factor authentication is already enabled for this user');
    });

    it('creates invalidTwoFactorCode exception', function (): void {
        $exception = UserException::invalidTwoFactorCode();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Invalid two-factor authentication code');
    });

    it('creates passwordTooWeak exception', function (): void {
        $exception = UserException::passwordTooWeak();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Password does not meet security requirements');
    });

    it('creates cannotPromoteToHigherRole exception', function (): void {
        $exception = UserException::cannotPromoteToHigherRole();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Cannot promote user to a role higher than your own');
    });

    it('creates cannotModifyOwnRole exception', function (): void {
        $exception = UserException::cannotModifyOwnRole();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Cannot modify your own role');
    });

    it('creates invalidStatusTransition exception', function (): void {
        $from = 'active';
        $to = 'blocked';
        $exception = UserException::invalidStatusTransition($from, $to);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("Invalid status transition from {$from} to {$to}");
    });

    it('creates profileUpdateFailed exception', function (): void {
        $reason = 'Invalid phone number format';
        $exception = UserException::profileUpdateFailed($reason);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("Profile update failed: {$reason}");
    });

    it('creates invalidJobTitle exception', function (): void {
        $jobTitle = 'InvalidTitle';
        $exception = UserException::invalidJobTitle($jobTitle);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("Invalid job title: {$jobTitle}");
    });

    it('creates phoneNumberAlreadyExists exception', function (): void {
        $phoneNumber = '+1-555-0123';
        $exception = UserException::phoneNumberAlreadyExists($phoneNumber);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("Phone number {$phoneNumber} is already in use");
    });

    it('creates maxUsersReached exception', function (): void {
        $exception = UserException::maxUsersReached();

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe('Maximum number of users has been reached');
    });

    it('creates bulkOperationFailed exception', function (): void {
        $operation = 'user_import';
        $failed = 5;
        $total = 10;
        $exception = UserException::bulkOperationFailed($operation, $failed, $total);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception->getMessage())->toBe("Bulk {$operation} operation failed: {$failed} of {$total} operations failed");
    });

    it('extends Exception class', function (): void {
        $exception = UserException::userNotFound(1);

        expect($exception)->toBeInstanceOf(Exception::class);
    });

    it('can be thrown and caught', function (): void {
        $caught = false;

        try {
            throw UserException::accountSuspended();
        } catch (UserException $e) {
            $caught = true;
            expect($e->getMessage())->toBe('User account has been suspended');
        }

        expect($caught)->toBeTrue();
    });

    it('maintains exception stack trace', function (): void {
        $exception = UserException::userNotFound(123);

        expect($exception->getTrace())->toBeArray()
            ->and($exception->getFile())->toBeString()
            ->and($exception->getLine())->toBeInt();
    });

    it('supports exception chaining', function (): void {
        $previousException = new Exception('Previous error');

        // UserException doesn't have explicit chaining in its static methods,
        // but we can test that it would work if we created one manually
        $exception = new UserException('Test message', 0, $previousException);

        expect($exception->getPrevious())->toBe($previousException);
    });

    it('handles edge cases in message formatting', function (): void {
        // Test with empty strings
        $exception = UserException::userNotFoundByEmail('');
        expect($exception->getMessage())->toBe('User with email  not found');

        // Test with special characters

        // Test with numeric values as strings
        $exception = UserException::profileUpdateFailed('123');
        expect($exception->getMessage())->toBe('Profile update failed: 123');
    });

    it('validates all static factory methods exist', function (): void {
        $reflection = new ReflectionClass(UserException::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

        $expectedMethods = [
            'userNotFound',
            'userNotFoundByEmail',
            'emailAlreadyExists',
            'cannotDeactivateAdmin',
            'cannotDeleteActiveUser',
            'insufficientPermissions',
            'accountSuspended',
            'accountBlocked',
            'accountNotVerified',
            'twoFactorNotEnabled',
            'twoFactorAlreadyEnabled',
            'invalidTwoFactorCode',
            'passwordTooWeak',
            'cannotPromoteToHigherRole',
            'cannotModifyOwnRole',
            'invalidStatusTransition',
            'profileUpdateFailed',
            'invalidJobTitle',
            'phoneNumberAlreadyExists',
            'maxUsersReached',
            'bulkOperationFailed',
        ];

        $actualMethodNames = array_map(fn ($method) => $method->getName(), $methods);

        foreach ($expectedMethods as $expectedMethod) {
            expect($actualMethodNames)->toContain($expectedMethod);
        }
    });

    it('ensures all factory methods return UserException instances', function (): void {
        $testCases = [
            ['userNotFound', [1]],
            ['userNotFoundByEmail', ['test@example.com']],
            ['emailAlreadyExists', ['test@example.com']],
            ['cannotDeactivateAdmin', []],
            ['cannotDeleteActiveUser', []],
            ['insufficientPermissions', ['test_action']],
            ['accountSuspended', []],
            ['accountBlocked', []],
            ['accountNotVerified', []],
            ['twoFactorNotEnabled', []],
            ['twoFactorAlreadyEnabled', []],
            ['invalidTwoFactorCode', []],
            ['passwordTooWeak', []],
            ['cannotPromoteToHigherRole', []],
            ['cannotModifyOwnRole', []],
            ['invalidStatusTransition', ['active', 'blocked']],
            ['profileUpdateFailed', ['test reason']],
            ['invalidJobTitle', ['test title']],
            ['phoneNumberAlreadyExists', ['+1-555-0123']],
            ['maxUsersReached', []],
            ['bulkOperationFailed', ['test_op', 1, 10]],
        ];

        foreach ($testCases as [$method, $args]) {
            $exception = UserException::$method(...$args);
            expect($exception)->toBeInstanceOf(UserException::class)
                ->and($exception->getMessage())->toBeString()
                ->and(strlen($exception->getMessage()))->toBeGreaterThan(0);
        }
    });

    it('validates exception codes are consistent', function (): void {
        // All UserException instances should have code 0 by default
        $exceptions = [
            UserException::userNotFound(1),
            UserException::accountSuspended(),
            UserException::maxUsersReached(),
        ];

        foreach ($exceptions as $exception) {
            expect($exception->getCode())->toBe(0);
        }
    });

    it('handles null and empty parameter cases gracefully', function (): void {
        // Test methods that accept nullable parameters - should not throw exceptions
        $exception1 = UserException::userNotFound(0);
        expect($exception1)->toBeInstanceOf(UserException::class);

        $exception2 = UserException::invalidStatusTransition('', '');
        expect($exception2)->toBeInstanceOf(UserException::class);

        $exception3 = UserException::bulkOperationFailed('', 0, 0);
        expect($exception3)->toBeInstanceOf(UserException::class);

        // Verify messages are still meaningful
        expect($exception2->getMessage())->toContain('Invalid status transition');
    });

    it('maintains proper inheritance hierarchy', function (): void {
        $exception = UserException::userNotFound(1);

        expect($exception)->toBeInstanceOf(UserException::class)
            ->and($exception)->toBeInstanceOf(Exception::class)
            ->and($exception)->toBeInstanceOf(Throwable::class);
    });
});
