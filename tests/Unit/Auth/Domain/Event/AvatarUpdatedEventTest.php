<?php

declare(strict_types=1);

use Modules\Auth\Domain\Event\AvatarUpdatedEvent;
use Modules\Shared\Domain\Event\DomainEventInterface;

describe('AvatarUpdatedEvent', function (): void {

    describe('Construction', function (): void {
        it('creates event with all parameters including photo URL and path', function (): void {
            $userId = 123;
            $photoUrl = 'https://example.com/avatars/user123.jpg';
            $photoPath = '/uploads/avatars/user123.jpg';
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00');

            $event = new AvatarUpdatedEvent($userId, $photoUrl, $photoPath, $occurredAt);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getPhotoUrl())->toBe($photoUrl)
                ->and($event->getPhotoPath())->toBe($photoPath)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('creates event with null photo URL and path', function (): void {
            $userId = 123;
            $event = new AvatarUpdatedEvent($userId, null, null);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getPhotoUrl())->toBeNull()
                ->and($event->getPhotoPath())->toBeNull();
        });

        it('creates event with default occurredAt when not provided', function (): void {
            $userId = 123;
            $photoUrl = 'https://example.com/avatars/user123.jpg';
            $photoPath = '/uploads/avatars/user123.jpg';

            $beforeCreation = new DateTimeImmutable;
            $event = new AvatarUpdatedEvent($userId, $photoUrl, $photoPath);
            $afterCreation = new DateTimeImmutable;

            expect($event->getOccurredAt())->toBeGreaterThanOrEqual($beforeCreation)
                ->and($event->getOccurredAt())->toBeLessThanOrEqual($afterCreation);
        });

        it('implements DomainEventInterface', function (): void {
            $event = new AvatarUpdatedEvent(123, null, null);

            expect($event)->toBeInstanceOf(DomainEventInterface::class);
        });
    });

    describe('Getters', function (): void {
        it('returns correct user ID', function (): void {
            $userId = 456;
            $event = new AvatarUpdatedEvent($userId, null, null);

            expect($event->getUserId())->toBe($userId);
        });

        it('returns correct photo URL when provided', function (): void {
            $photoUrl = 'https://cdn.example.com/images/profile/789.png';
            $event = new AvatarUpdatedEvent(123, $photoUrl, null);

            expect($event->getPhotoUrl())->toBe($photoUrl);
        });

        it('returns null photo URL when not provided', function (): void {
            $event = new AvatarUpdatedEvent(123, null, '/path/to/photo.jpg');

            expect($event->getPhotoUrl())->toBeNull();
        });

        it('returns correct photo path when provided', function (): void {
            $photoPath = '/var/www/uploads/users/profile_photos/user123.jpg';
            $event = new AvatarUpdatedEvent(123, null, $photoPath);

            expect($event->getPhotoPath())->toBe($photoPath);
        });

        it('returns null photo path when not provided', function (): void {
            $event = new AvatarUpdatedEvent(123, 'https://example.com/photo.jpg', null);

            expect($event->getPhotoPath())->toBeNull();
        });

        it('returns correct occurred at timestamp', function (): void {
            $occurredAt = new DateTimeImmutable('2025-06-15 14:30:00');
            $event = new AvatarUpdatedEvent(123, null, null, $occurredAt);

            expect($event->getOccurredAt())->toBe($occurredAt);
        });
    });

    describe('Domain Event Interface Methods', function (): void {
        it('returns correct event name', function (): void {
            $event = new AvatarUpdatedEvent(123, null, null);

            expect($event->getEventName())->toBe('auth.avatar.updated');
        });

        it('returns correct event data with all fields', function (): void {
            $userId = 789;
            $photoUrl = 'https://storage.example.com/avatars/789.webp';
            $photoPath = '/app/storage/avatars/789.webp';
            $occurredAt = new DateTimeImmutable('2025-03-01 09:15:30');

            $event = new AvatarUpdatedEvent($userId, $photoUrl, $photoPath, $occurredAt);

            $expectedData = [
                'user_id' => $userId,
                'photo_url' => $photoUrl,
                'photo_path' => $photoPath,
                'occurred_at' => $occurredAt->format(DateTimeImmutable::ATOM),
            ];

            expect($event->getEventData())->toBe($expectedData);
        });

        it('returns correct event data with null values', function (): void {
            $userId = 555;
            $occurredAt = new DateTimeImmutable('2025-04-15 16:45:00');

            $event = new AvatarUpdatedEvent($userId, null, null, $occurredAt);

            $expectedData = [
                'user_id' => $userId,
                'photo_url' => null,
                'photo_path' => null,
                'occurred_at' => $occurredAt->format(DateTimeImmutable::ATOM),
            ];

            expect($event->getEventData())->toBe($expectedData);
        });

        it('returns correct aggregate ID', function (): void {
            $userId = 555;
            $event = new AvatarUpdatedEvent($userId, null, null);

            expect($event->getAggregateId())->toBe($userId);
        });

        it('returns correct event version', function (): void {
            $event = new AvatarUpdatedEvent(123, null, null);

            expect($event->getEventVersion())->toBe(1);
        });

        it('returns correct context', function (): void {
            $event = new AvatarUpdatedEvent(123, null, null);

            expect($event->getContext())->toBe('Auth');
        });

        it('indicates event is not async', function (): void {
            $event = new AvatarUpdatedEvent(123, null, null);

            expect($event->isAsync())->toBeFalse();
        });
    });

    describe('Avatar Scenarios', function (): void {
        it('handles new avatar upload scenario', function (): void {
            $event = new AvatarUpdatedEvent(
                userId: 123,
                photoUrl: 'https://cdn.example.com/avatars/new_photo.jpg',
                photoPath: '/storage/app/public/avatars/new_photo.jpg'
            );

            expect($event->getPhotoUrl())->not->toBeNull()
                ->and($event->getPhotoPath())->not->toBeNull();
        });

        it('handles avatar removal scenario', function (): void {
            $event = new AvatarUpdatedEvent(
                userId: 123,
                photoUrl: null,
                photoPath: null
            );

            expect($event->getPhotoUrl())->toBeNull()
                ->and($event->getPhotoPath())->toBeNull();
        });

        it('handles avatar URL update without path change', function (): void {
            $event = new AvatarUpdatedEvent(
                userId: 123,
                photoUrl: 'https://new-cdn.example.com/avatars/user123.jpg',
                photoPath: '/same/path/user123.jpg'
            );

            expect($event->getPhotoUrl())->toContain('new-cdn.example.com')
                ->and($event->getPhotoPath())->toContain('/same/path/');
        });

        it('handles avatar path update without URL change', function (): void {
            $event = new AvatarUpdatedEvent(
                userId: 123,
                photoUrl: 'https://cdn.example.com/avatars/user123.jpg',
                photoPath: '/new/storage/location/user123.jpg'
            );

            expect($event->getPhotoUrl())->toContain('cdn.example.com')
                ->and($event->getPhotoPath())->toContain('/new/storage/location/');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero user ID', function (): void {
            $event = new AvatarUpdatedEvent(0, null, null);

            expect($event->getUserId())->toBe(0)
                ->and($event->getAggregateId())->toBe(0);
        });

        it('handles negative user ID', function (): void {
            $event = new AvatarUpdatedEvent(-1, null, null);

            expect($event->getUserId())->toBe(-1)
                ->and($event->getAggregateId())->toBe(-1);
        });

        it('handles empty string photo URL', function (): void {
            $event = new AvatarUpdatedEvent(123, '', null);

            expect($event->getPhotoUrl())->toBe('');
        });

        it('handles empty string photo path', function (): void {
            $event = new AvatarUpdatedEvent(123, null, '');

            expect($event->getPhotoPath())->toBe('');
        });

        it('handles very long photo URLs', function (): void {
            $longUrl = 'https://very-long-domain-name.example.com/extremely/long/path/to/avatars/with/many/subdirectories/' . str_repeat('long-filename-', 20) . '.jpg';
            $event = new AvatarUpdatedEvent(123, $longUrl, null);

            expect($event->getPhotoUrl())->toBe($longUrl);
        });

        it('handles very long photo paths', function (): void {
            $longPath = '/very/long/absolute/path/to/storage/with/many/nested/directories/' . str_repeat('subdirectory/', 10) . 'filename.jpg';
            $event = new AvatarUpdatedEvent(123, null, $longPath);

            expect($event->getPhotoPath())->toBe($longPath);
        });

        it('handles special characters in photo URL', function (): void {
            $urlWithSpecialChars = 'https://example.com/avatars/用户头像.jpg?v=1&token=abc123';
            $event = new AvatarUpdatedEvent(123, $urlWithSpecialChars, null);

            expect($event->getPhotoUrl())->toBe($urlWithSpecialChars);
        });

        it('handles special characters in photo path', function (): void {
            $pathWithSpecialChars = '/uploads/头像/user 123 (copy).jpg';
            $event = new AvatarUpdatedEvent(123, null, $pathWithSpecialChars);

            expect($event->getPhotoPath())->toBe($pathWithSpecialChars);
        });
    });

    describe('Real-world File Scenarios', function (): void {
        it('handles common image file extensions', function (): void {
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

            foreach ($extensions as $ext) {
                $photoUrl = "https://cdn.example.com/avatars/user123.{$ext}";
                $photoPath = "/storage/avatars/user123.{$ext}";

                $event = new AvatarUpdatedEvent(123, $photoUrl, $photoPath);

                expect($event->getPhotoUrl())->toEndWith(".{$ext}")
                    ->and($event->getPhotoPath())->toEndWith(".{$ext}");
            }
        });

        it('handles AWS S3 style URLs', function (): void {
            $s3Url = 'https://my-bucket.s3.amazonaws.com/avatars/user-123/profile-photo.jpg';
            $event = new AvatarUpdatedEvent(123, $s3Url, null);

            expect($event->getPhotoUrl())->toContain('s3.amazonaws.com');
        });

        it('handles CDN URLs with query parameters', function (): void {
            $cdnUrl = 'https://cdn.example.com/avatars/user123.jpg?w=200&h=200&q=85&v=2';
            $event = new AvatarUpdatedEvent(123, $cdnUrl, null);

            expect($event->getPhotoUrl())->toContain('?w=200&h=200&q=85&v=2');
        });

        it('handles relative photo paths', function (): void {
            $relativePath = 'storage/app/public/avatars/user123.jpg';
            $event = new AvatarUpdatedEvent(123, null, $relativePath);

            expect($event->getPhotoPath())->toBe($relativePath);
        });

        it('handles Windows-style paths', function (): void {
            $windowsPath = 'C:\\inetpub\\wwwroot\\uploads\\avatars\\user123.jpg';
            $event = new AvatarUpdatedEvent(123, null, $windowsPath);

            expect($event->getPhotoPath())->toBe($windowsPath);
        });

        it('handles Unix-style absolute paths', function (): void {
            $unixPath = '/var/www/html/storage/app/public/avatars/user123.jpg';
            $event = new AvatarUpdatedEvent(123, null, $unixPath);

            expect($event->getPhotoPath())->toBe($unixPath);
        });
    });

    describe('Immutability', function (): void {
        it('creates immutable event object', function (): void {
            $userId = 123;
            $photoUrl = 'https://example.com/photo.jpg';
            $photoPath = '/path/to/photo.jpg';
            $occurredAt = new DateTimeImmutable;

            $event = new AvatarUpdatedEvent($userId, $photoUrl, $photoPath, $occurredAt);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getPhotoUrl())->toBe($photoUrl)
                ->and($event->getPhotoPath())->toBe($photoPath)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('maintains consistent event data across multiple calls', function (): void {
            $event = new AvatarUpdatedEvent(123, 'https://example.com/photo.jpg', '/path/photo.jpg');

            $firstCall = $event->getEventData();
            $secondCall = $event->getEventData();

            expect($firstCall)->toBe($secondCall);
        });
    });

    describe('Event Data Structure', function (): void {
        it('includes all required fields in event data', function (): void {
            $event = new AvatarUpdatedEvent(123, 'https://example.com/photo.jpg', '/path/photo.jpg');
            $eventData = $event->getEventData();

            expect($eventData)->toHaveKeys([
                'user_id',
                'photo_url',
                'photo_path',
                'occurred_at',
            ]);
        });

        it('has consistent data types in event data', function (): void {
            $event = new AvatarUpdatedEvent(123, 'https://example.com/photo.jpg', '/path/photo.jpg');
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBeInt()
                ->and($eventData['photo_url'])->toBeString()
                ->and($eventData['photo_path'])->toBeString()
                ->and($eventData['occurred_at'])->toBeString();
        });

        it('handles null values correctly in event data', function (): void {
            $event = new AvatarUpdatedEvent(123, null, null);
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBeInt()
                ->and($eventData['photo_url'])->toBeNull()
                ->and($eventData['photo_path'])->toBeNull()
                ->and($eventData['occurred_at'])->toBeString();
        });

        it('maintains data integrity across serialization', function (): void {
            $userId = 789;
            $photoUrl = 'https://storage.example.com/avatars/789.jpg';
            $photoPath = '/app/storage/avatars/789.jpg';
            $occurredAt = new DateTimeImmutable('2025-05-15 10:30:45');

            $event = new AvatarUpdatedEvent($userId, $photoUrl, $photoPath, $occurredAt);
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBe($userId)
                ->and($eventData['photo_url'])->toBe($photoUrl)
                ->and($eventData['photo_path'])->toBe($photoPath)
                ->and($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });
    });

    describe('Comparison with Other Auth Events', function (): void {
        it('has different event name than other auth events', function (): void {
            $avatarEvent = new AvatarUpdatedEvent(123, null, null);

            expect($avatarEvent->getEventName())->toBe('auth.avatar.updated')
                ->and($avatarEvent->getEventName())->not->toBe('auth.password.changed')
                ->and($avatarEvent->getEventName())->not->toBe('auth.two_factor.enabled');
        });

        it('has unique photo-related fields', function (): void {
            $avatarEvent = new AvatarUpdatedEvent(123, 'url', 'path');
            $eventData = $avatarEvent->getEventData();

            expect($eventData)->toHaveKey('photo_url')
                ->and($eventData)->toHaveKey('photo_path');
        });

        it('shares common fields with other auth events', function (): void {
            $avatarEvent = new AvatarUpdatedEvent(123, null, null);
            $eventData = $avatarEvent->getEventData();

            $commonFields = ['user_id', 'occurred_at'];

            foreach ($commonFields as $field) {
                expect($eventData)->toHaveKey($field);
            }
        });

        it('does not include IP address or user agent like other auth events', function (): void {
            $avatarEvent = new AvatarUpdatedEvent(123, null, null);
            $eventData = $avatarEvent->getEventData();

            expect($eventData)->not->toHaveKey('ip_address')
                ->and($eventData)->not->toHaveKey('user_agent');
        });
    });

    describe('Date Formatting', function (): void {
        it('formats occurred at timestamp correctly in event data', function (): void {
            $occurredAt = new DateTimeImmutable('2025-12-25 15:45:30');
            $event = new AvatarUpdatedEvent(123, null, null, $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });

        it('handles different timezone formats', function (): void {
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00', new DateTimeZone('Asia/Tokyo'));
            $event = new AvatarUpdatedEvent(123, null, null, $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });
    });
});
