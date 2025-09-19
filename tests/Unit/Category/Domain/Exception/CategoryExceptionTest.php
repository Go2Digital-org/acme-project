<?php

declare(strict_types=1);

use Modules\Category\Domain\Exception\CategoryException;

describe('CategoryException', function (): void {
    describe('slugAlreadyExists', function (): void {
        it('creates exception with correct message for string slug', function (): void {
            $slug = 'environment';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception)->toBeInstanceOf(RuntimeException::class)
                ->and($exception->getMessage())->toBe("Category with slug 'environment' already exists");
        });

        it('creates exception with correct message for slug with special characters', function (): void {
            $slug = 'environment_protection-2024';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe("Category with slug 'environment_protection-2024' already exists");
        });

        it('creates exception with correct message for short slug', function (): void {
            $slug = 'a';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe("Category with slug 'a' already exists");
        });

        it('creates exception with correct message for long slug', function (): void {
            $slug = 'very_long_category_slug_that_contains_many_words_and_underscores';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe("Category with slug 'very_long_category_slug_that_contains_many_words_and_underscores' already exists");
        });

        it('creates exception with correct message for numeric slug', function (): void {
            $slug = '123456';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe("Category with slug '123456' already exists");
        });

        it('creates exception with correct message for empty string', function (): void {
            $slug = '';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe("Category with slug '' already exists");
        });

        it('has default exception code', function (): void {
            $exception = CategoryException::slugAlreadyExists('test');

            expect($exception->getCode())->toBe(0);
        });

        it('has no previous exception', function (): void {
            $exception = CategoryException::slugAlreadyExists('test');

            expect($exception->getPrevious())->toBeNull();
        });

        it('message format is consistent', function (): void {
            $slugs = ['test', 'environment', 'health_care', 'category-123'];

            foreach ($slugs as $slug) {
                $exception = CategoryException::slugAlreadyExists($slug);
                expect($exception->getMessage())->toMatch("/^Category with slug '.*' already exists$/");
            }
        });
    });

    describe('notFound', function (): void {
        it('creates exception with generic message when no id provided', function (): void {
            $exception = CategoryException::notFound();

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception)->toBeInstanceOf(RuntimeException::class)
                ->and($exception->getMessage())->toBe('Category not found');
        });

        it('creates exception with generic message when null id provided', function (): void {
            $exception = CategoryException::notFound(null);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category not found');
        });

        it('creates exception with specific message for integer id', function (): void {
            $exception = CategoryException::notFound(123);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID 123 not found');
        });

        it('creates exception with specific message for string id', function (): void {
            $exception = CategoryException::notFound('abc123');

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID abc123 not found');
        });

        it('creates exception with specific message for zero id', function (): void {
            $exception = CategoryException::notFound(0);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID 0 not found');
        });

        it('creates exception with specific message for negative id', function (): void {
            $exception = CategoryException::notFound(-1);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID -1 not found');
        });

        it('creates exception with specific message for large integer id', function (): void {
            $exception = CategoryException::notFound(999999999);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID 999999999 not found');
        });

        it('creates exception with specific message for empty string id', function (): void {
            $exception = CategoryException::notFound('');

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID  not found');
        });

        it('has default exception code', function (): void {
            $exception = CategoryException::notFound(123);

            expect($exception->getCode())->toBe(0);
        });

        it('has no previous exception', function (): void {
            $exception = CategoryException::notFound(123);

            expect($exception->getPrevious())->toBeNull();
        });

        it('message format is consistent for ids', function (): void {
            $ids = [1, 123, 'abc', '123abc', 0, -1];

            foreach ($ids as $id) {
                $exception = CategoryException::notFound($id);
                expect($exception->getMessage())->toMatch('/^Category with ID .* not found$/');
            }
        });
    });

    describe('categoryNotFound', function (): void {
        it('creates exception with specific message for positive integer id', function (): void {
            $exception = CategoryException::categoryNotFound(42);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception)->toBeInstanceOf(RuntimeException::class)
                ->and($exception->getMessage())->toBe('Category with ID 42 not found');
        });

        it('creates exception with specific message for zero id', function (): void {
            $exception = CategoryException::categoryNotFound(0);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID 0 not found');
        });

        it('creates exception with specific message for negative id', function (): void {
            $exception = CategoryException::categoryNotFound(-5);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID -5 not found');
        });

        it('creates exception with specific message for large integer id', function (): void {
            $exception = CategoryException::categoryNotFound(2147483647);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Category with ID 2147483647 not found');
        });

        it('has default exception code', function (): void {
            $exception = CategoryException::categoryNotFound(123);

            expect($exception->getCode())->toBe(0);
        });

        it('has no previous exception', function (): void {
            $exception = CategoryException::categoryNotFound(123);

            expect($exception->getPrevious())->toBeNull();
        });

        it('message format is consistent', function (): void {
            $ids = [1, 42, 100, 999, 0, -1, -100];

            foreach ($ids as $id) {
                $exception = CategoryException::categoryNotFound($id);
                expect($exception->getMessage())->toMatch("/^Category with ID -?\d+ not found$/");
            }
        });

        it('is equivalent to notFound for integer ids', function (): void {
            $id = 123;
            $exception1 = CategoryException::categoryNotFound($id);
            $exception2 = CategoryException::notFound($id);

            expect($exception1->getMessage())->toBe($exception2->getMessage());
        });
    });

    describe('cannotDeleteCategoryWithCampaigns', function (): void {
        it('creates exception with specific message for positive integer id', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(15);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception)->toBeInstanceOf(RuntimeException::class)
                ->and($exception->getMessage())->toBe('Cannot delete category with ID 15 because it has associated campaigns');
        });

        it('creates exception with specific message for zero id', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(0);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Cannot delete category with ID 0 because it has associated campaigns');
        });

        it('creates exception with specific message for negative id', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(-10);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Cannot delete category with ID -10 because it has associated campaigns');
        });

        it('creates exception with specific message for large integer id', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(999999);

            expect($exception)->toBeInstanceOf(CategoryException::class)
                ->and($exception->getMessage())->toBe('Cannot delete category with ID 999999 because it has associated campaigns');
        });

        it('has default exception code', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(123);

            expect($exception->getCode())->toBe(0);
        });

        it('has no previous exception', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(123);

            expect($exception->getPrevious())->toBeNull();
        });

        it('message format is consistent', function (): void {
            $ids = [1, 25, 100, 500, 0, -1, -50];

            foreach ($ids as $id) {
                $exception = CategoryException::cannotDeleteCategoryWithCampaigns($id);
                expect($exception->getMessage())->toMatch("/^Cannot delete category with ID -?\d+ because it has associated campaigns$/");
            }
        });

        it('message clearly explains the constraint', function (): void {
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns(123);
            $message = $exception->getMessage();

            expect($message)->toContain('Cannot delete')
                ->and($message)->toContain('associated campaigns')
                ->and($message)->toContain('category with ID 123');
        });
    });

    describe('Exception Inheritance', function (): void {
        it('all factory methods return CategoryException instances', function (): void {
            $exceptions = [
                CategoryException::slugAlreadyExists('test'),
                CategoryException::notFound(123),
                CategoryException::categoryNotFound(123),
                CategoryException::cannotDeleteCategoryWithCampaigns(123),
            ];

            foreach ($exceptions as $exception) {
                expect($exception)->toBeInstanceOf(CategoryException::class)
                    ->and($exception)->toBeInstanceOf(RuntimeException::class)
                    ->and($exception)->toBeInstanceOf(Exception::class);
            }
        });

        it('all exceptions have string messages', function (): void {
            $exceptions = [
                CategoryException::slugAlreadyExists('test'),
                CategoryException::notFound(123),
                CategoryException::categoryNotFound(123),
                CategoryException::cannotDeleteCategoryWithCampaigns(123),
            ];

            foreach ($exceptions as $exception) {
                expect($exception->getMessage())->toBeString()
                    ->and($exception->getMessage())->not->toBeEmpty();
            }
        });

        it('all exceptions have integer codes', function (): void {
            $exceptions = [
                CategoryException::slugAlreadyExists('test'),
                CategoryException::notFound(123),
                CategoryException::categoryNotFound(123),
                CategoryException::cannotDeleteCategoryWithCampaigns(123),
            ];

            foreach ($exceptions as $exception) {
                expect($exception->getCode())->toBeInt();
            }
        });

        it('all exceptions can be thrown and caught', function (): void {
            $exceptions = [
                CategoryException::slugAlreadyExists('test'),
                CategoryException::notFound(123),
                CategoryException::categoryNotFound(123),
                CategoryException::cannotDeleteCategoryWithCampaigns(123),
            ];

            foreach ($exceptions as $exception) {
                try {
                    throw $exception;
                } catch (CategoryException $caught) {
                    expect($caught)->toBe($exception);
                } catch (Exception $caught) {
                    $this->fail('Should have caught CategoryException');
                }
            }
        });
    });

    describe('Message Uniqueness', function (): void {
        it('different exception types have different messages', function (): void {
            $messages = [
                CategoryException::slugAlreadyExists('test')->getMessage(),
                CategoryException::notFound(123)->getMessage(),
                CategoryException::categoryNotFound(123)->getMessage(),
                CategoryException::cannotDeleteCategoryWithCampaigns(123)->getMessage(),
            ];

            // Note: notFound and categoryNotFound will have same message for integer ids
            $uniqueMessages = array_unique($messages);
            expect(count($uniqueMessages))->toBeGreaterThanOrEqual(3);
        });

        it('same exception type with different parameters have different messages', function (): void {
            $slugMessages = [
                CategoryException::slugAlreadyExists('environment')->getMessage(),
                CategoryException::slugAlreadyExists('health')->getMessage(),
                CategoryException::slugAlreadyExists('education')->getMessage(),
            ];

            expect(array_unique($slugMessages))->toHaveCount(3);

            $notFoundMessages = [
                CategoryException::notFound(1)->getMessage(),
                CategoryException::notFound(2)->getMessage(),
                CategoryException::notFound(3)->getMessage(),
            ];

            expect(array_unique($notFoundMessages))->toHaveCount(3);
        });
    });

    describe('Error Context', function (): void {
        it('slug already exists exception contains the problematic slug', function (): void {
            $slug = 'environment-protection-2024';
            $exception = CategoryException::slugAlreadyExists($slug);

            expect($exception->getMessage())->toContain($slug);
        });

        it('not found exceptions contain the requested id', function (): void {
            $id = 12345;
            $exception1 = CategoryException::notFound($id);
            $exception2 = CategoryException::categoryNotFound($id);

            expect($exception1->getMessage())->toContain((string) $id)
                ->and($exception2->getMessage())->toContain((string) $id);
        });

        it('cannot delete exception contains the category id', function (): void {
            $id = 98765;
            $exception = CategoryException::cannotDeleteCategoryWithCampaigns($id);

            expect($exception->getMessage())->toContain((string) $id);
        });

        it('exception messages are suitable for logging', function (): void {
            $exceptions = [
                CategoryException::slugAlreadyExists('test'),
                CategoryException::notFound(123),
                CategoryException::categoryNotFound(123),
                CategoryException::cannotDeleteCategoryWithCampaigns(123),
            ];

            foreach ($exceptions as $exception) {
                $message = $exception->getMessage();
                // Messages should be descriptive and contain relevant context
                expect(strlen($message))->toBeGreaterThan(10)
                    ->and($message)->not->toContain('\n')
                    ->and($message)->not->toContain('\t');
            }
        });
    });
});
