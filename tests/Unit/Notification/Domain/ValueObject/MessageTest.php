<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObject\Message;

describe('Message Value Object', function (): void {
    describe('Construction', function (): void {
        it('creates message with valid subject and body', function (): void {
            $message = new Message('Test Subject', 'Test body content');

            expect($message->subject)->toBe('Test Subject')
                ->and($message->body)->toBe('Test body content')
                ->and($message->htmlBody)->toBeNull();
        });

        it('creates message with HTML body', function (): void {
            $message = new Message(
                'Test Subject',
                'Plain text body',
                '<p>HTML body content</p>'
            );

            expect($message->subject)->toBe('Test Subject')
                ->and($message->body)->toBe('Plain text body')
                ->and($message->htmlBody)->toBe('<p>HTML body content</p>');
        });

        it('throws exception for empty subject', function (): void {
            expect(fn () => new Message('', 'Valid body'))
                ->toThrow(InvalidArgumentException::class, 'Message subject cannot be empty');
        });

        it('throws exception for whitespace-only subject', function (): void {
            expect(fn () => new Message('   ', 'Valid body'))
                ->toThrow(InvalidArgumentException::class, 'Message subject cannot be empty');
        });

        it('throws exception for empty body', function (): void {
            expect(fn () => new Message('Valid subject', ''))
                ->toThrow(InvalidArgumentException::class, 'Message body cannot be empty');
        });

        it('throws exception for whitespace-only body', function (): void {
            expect(fn () => new Message('Valid subject', '   '))
                ->toThrow(InvalidArgumentException::class, 'Message body cannot be empty');
        });

        it('throws exception for subject longer than 255 characters', function (): void {
            $longSubject = str_repeat('a', 256);

            expect(fn () => new Message($longSubject, 'Valid body'))
                ->toThrow(InvalidArgumentException::class, 'Message subject cannot exceed 255 characters');
        });

        it('accepts subject exactly 255 characters', function (): void {
            $subject255 = str_repeat('a', 255);
            $message = new Message($subject255, 'Valid body');

            expect($message->subject)->toBe($subject255)
                ->and(strlen($message->subject))->toBe(255);
        });

        it('throws exception for body longer than 65535 characters', function (): void {
            $longBody = str_repeat('a', 65536);

            expect(fn () => new Message('Valid subject', $longBody))
                ->toThrow(InvalidArgumentException::class, 'Message body cannot exceed 65535 characters');
        });

        it('accepts body exactly 65535 characters', function (): void {
            $body65535 = str_repeat('a', 65535);
            $message = new Message('Valid subject', $body65535);

            expect($message->body)->toBe($body65535)
                ->and(strlen($message->body))->toBe(65535);
        });

        it('throws exception for HTML body longer than 65535 characters', function (): void {
            $longHtmlBody = str_repeat('a', 65536);

            expect(fn () => new Message('Valid subject', 'Valid body', $longHtmlBody))
                ->toThrow(InvalidArgumentException::class, 'Message HTML body cannot exceed 65535 characters');
        });

        it('accepts HTML body exactly 65535 characters', function (): void {
            $htmlBody65535 = str_repeat('a', 65535);
            $message = new Message('Valid subject', 'Valid body', $htmlBody65535);

            expect($message->htmlBody)->toBe($htmlBody65535)
                ->and(strlen($message->htmlBody))->toBe(65535);
        });

        it('trims whitespace from subject and body validation', function (): void {
            $message = new Message('  Valid Subject  ', '  Valid Body  ');

            expect($message->subject)->toBe('  Valid Subject  ')
                ->and($message->body)->toBe('  Valid Body  ');
        });

        it('handles special characters in subject and body', function (): void {
            $subject = 'Subject with émojis 🎉 and spéciàl chars';
            $body = 'Body with line breaks\nand tabs\tand émojis 🚀';

            $message = new Message($subject, $body);

            expect($message->subject)->toBe($subject)
                ->and($message->body)->toBe($body);
        });

        it('handles null HTML body correctly', function (): void {
            $message = new Message('Subject', 'Body', null);

            expect($message->htmlBody)->toBeNull();
        });
    });

    describe('Factory Methods', function (): void {
        it('creates plain text message via factory method', function (): void {
            $message = Message::plainText('Test Subject', 'Test body');

            expect($message->subject)->toBe('Test Subject')
                ->and($message->body)->toBe('Test body')
                ->and($message->htmlBody)->toBeNull();
        });

        it('creates HTML message via factory method', function (): void {
            $message = Message::withHtml(
                'Test Subject',
                'Plain text version',
                '<h1>HTML Version</h1>'
            );

            expect($message->subject)->toBe('Test Subject')
                ->and($message->body)->toBe('Plain text version')
                ->and($message->htmlBody)->toBe('<h1>HTML Version</h1>');
        });

        it('factory methods validate input same as constructor', function (): void {
            expect(fn () => Message::plainText('', 'Valid body'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => Message::withHtml('Valid subject', '', '<p>HTML</p>'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('factory methods produce equivalent objects to constructor', function (): void {
            $message1 = new Message('Subject', 'Body');
            $message2 = Message::plainText('Subject', 'Body');

            expect($message1->equals($message2))->toBeTrue();

            $message3 = new Message('Subject', 'Body', '<p>HTML</p>');
            $message4 = Message::withHtml('Subject', 'Body', '<p>HTML</p>');

            expect($message3->equals($message4))->toBeTrue();
        });
    });

    describe('hasHtmlBody() method', function (): void {
        it('returns false when HTML body is null', function (): void {
            $message = new Message('Subject', 'Body');

            expect($message->hasHtmlBody())->toBeFalse();
        });

        it('returns true when HTML body is present', function (): void {
            $message = new Message('Subject', 'Body', '<p>HTML</p>');

            expect($message->hasHtmlBody())->toBeTrue();
        });

        it('returns true even for empty HTML string', function (): void {
            $message = new Message('Subject', 'Body', '');

            expect($message->hasHtmlBody())->toBeTrue();
        });

        it('returns true for whitespace-only HTML body', function (): void {
            $message = new Message('Subject', 'Body', '   ');

            expect($message->hasHtmlBody())->toBeTrue();
        });
    });

    describe('getPreview() method', function (): void {
        it('returns full body when shorter than limit', function (): void {
            $message = new Message('Subject', 'Short body');

            expect($message->getPreview())->toBe('Short body')
                ->and($message->getPreview(50))->toBe('Short body');
        });

        it('truncates body when longer than limit', function (): void {
            $longBody = str_repeat('a', 150);
            $message = new Message('Subject', $longBody);

            $preview = $message->getPreview(100);
            expect($preview)->toBe(str_repeat('a', 100) . '...')
                ->and(strlen($preview))->toBe(103);
        });

        it('uses default limit of 100 characters', function (): void {
            $longBody = str_repeat('b', 150);
            $message = new Message('Subject', $longBody);

            $preview = $message->getPreview();
            expect($preview)->toBe(str_repeat('b', 100) . '...')
                ->and(strlen($preview))->toBe(103);
        });

        it('strips HTML tags from body preview', function (): void {
            $htmlBody = '<p>This is <strong>bold</strong> text with <em>emphasis</em></p>';
            $message = new Message('Subject', $htmlBody);

            $preview = $message->getPreview();
            expect($preview)->toBe('This is bold text with emphasis');
        });

        it('handles complex HTML in body', function (): void {
            $htmlBody = '<div><h1>Title</h1><p>Paragraph with <a href="url">link</a></p></div>';
            $message = new Message('Subject', $htmlBody);

            $preview = $message->getPreview();
            expect($preview)->toBe('TitleParagraph with link');
        });

        it('handles newlines and whitespace in body', function (): void {
            $bodyWithNewlines = "Line 1\n\nLine 2\tTabbed\r\nLine 3";
            $message = new Message('Subject', $bodyWithNewlines);

            $preview = $message->getPreview();
            expect($preview)->toBe("Line 1\n\nLine 2\tTabbed\r\nLine 3");
        });

        it('respects custom length limits', function (): void {
            $body = 'This is a test message body';
            $message = new Message('Subject', $body);

            expect($message->getPreview(10))->toBe('This is a ...')
                ->and($message->getPreview(20))->toBe('This is a test messa...')
                ->and($message->getPreview(4))->toBe('This...')
                ->and($message->getPreview(1))->toBe('T...');
        });

        it('handles zero and negative length limits gracefully', function (): void {
            $message = new Message('Subject', 'Test body');

            expect($message->getPreview(0))->toBe('...')
                ->and($message->getPreview(-5))->toBe('Test...');
        });

        it('handles unicode characters correctly', function (): void {
            $unicodeBody = 'Héllo wørld with émojis 🎉🚀';
            $message = new Message('Subject', $unicodeBody);

            $preview = $message->getPreview(15);
            expect(strlen($preview))->toBeLessThanOrEqual(18); // Account for ellipsis
        });

        it('preserves exact length when body equals limit', function (): void {
            $exactBody = str_repeat('a', 100);
            $message = new Message('Subject', $exactBody);

            expect($message->getPreview(100))->toBe($exactBody)
                ->and(strlen($message->getPreview(100)))->toBe(100);
        });
    });

    describe('equals() method', function (): void {
        it('returns true for identical messages', function (): void {
            $message1 = new Message('Subject', 'Body');
            $message2 = new Message('Subject', 'Body');

            expect($message1->equals($message2))->toBeTrue()
                ->and($message2->equals($message1))->toBeTrue();
        });

        it('returns true for identical messages with HTML', function (): void {
            $message1 = new Message('Subject', 'Body', '<p>HTML</p>');
            $message2 = new Message('Subject', 'Body', '<p>HTML</p>');

            expect($message1->equals($message2))->toBeTrue();
        });

        it('returns false for different subjects', function (): void {
            $message1 = new Message('Subject 1', 'Body');
            $message2 = new Message('Subject 2', 'Body');

            expect($message1->equals($message2))->toBeFalse();
        });

        it('returns false for different bodies', function (): void {
            $message1 = new Message('Subject', 'Body 1');
            $message2 = new Message('Subject', 'Body 2');

            expect($message1->equals($message2))->toBeFalse();
        });

        it('returns false for different HTML bodies', function (): void {
            $message1 = new Message('Subject', 'Body', '<p>HTML 1</p>');
            $message2 = new Message('Subject', 'Body', '<p>HTML 2</p>');

            expect($message1->equals($message2))->toBeFalse();
        });

        it('returns false when one has HTML body and other does not', function (): void {
            $message1 = new Message('Subject', 'Body');
            $message2 = new Message('Subject', 'Body', '<p>HTML</p>');

            expect($message1->equals($message2))->toBeFalse()
                ->and($message2->equals($message1))->toBeFalse();
        });

        it('is case-sensitive for all fields', function (): void {
            $message1 = new Message('Subject', 'Body', '<p>HTML</p>');
            $message2 = new Message('SUBJECT', 'BODY', '<P>HTML</P>');

            expect($message1->equals($message2))->toBeFalse();
        });

        it('is whitespace-sensitive', function (): void {
            $message1 = new Message('Subject', 'Body');
            $message2 = new Message(' Subject ', ' Body ');

            expect($message1->equals($message2))->toBeFalse();
        });

        it('handles reflexive equality', function (): void {
            $message = new Message('Subject', 'Body', '<p>HTML</p>');

            expect($message->equals($message))->toBeTrue();
        });
    });

    describe('__toString() method', function (): void {
        it('returns subject as string representation', function (): void {
            $message = new Message('Test Subject', 'Test Body');

            expect((string) $message)->toBe('Test Subject')
                ->and($message->__toString())->toBe('Test Subject');
        });

        it('returns subject even with HTML body present', function (): void {
            $message = new Message('Email Subject', 'Plain body', '<h1>HTML</h1>');

            expect((string) $message)->toBe('Email Subject');
        });

        it('handles special characters in subject', function (): void {
            $subject = 'Subject with émojis 🎉 and spéciàl chars';
            $message = new Message($subject, 'Body');

            expect((string) $message)->toBe($subject);
        });

        it('returns long subjects completely', function (): void {
            $longSubject = str_repeat('A', 255);
            $message = new Message($longSubject, 'Body');

            expect((string) $message)->toBe($longSubject)
                ->and(strlen((string) $message))->toBe(255);
        });
    });

    describe('Stringable Interface', function (): void {
        it('implements Stringable interface', function (): void {
            $message = new Message('Subject', 'Body');

            expect($message)->toBeInstanceOf(Stringable::class);
        });

        it('can be used in string context', function (): void {
            $message = new Message('Test Subject', 'Body');
            $concatenated = 'Prefix: ' . $message . ' :Suffix';

            expect($concatenated)->toBe('Prefix: Test Subject :Suffix');
        });

        it('can be used in string interpolation', function (): void {
            $message = new Message('Notification', 'Body');
            $interpolated = "Message: {$message}";

            expect($interpolated)->toBe('Message: Notification');
        });
    });

    describe('Immutability', function (): void {
        it('has readonly properties', function (): void {
            $message = new Message('Subject', 'Body', '<p>HTML</p>');

            expect($message->subject)->toBe('Subject')
                ->and($message->body)->toBe('Body')
                ->and($message->htmlBody)->toBe('<p>HTML</p>');
        });

        it('cannot modify subject property', function (): void {
            $message = new Message('Original', 'Body');

            // This should fail at PHP level due to readonly
            expect(function () use ($message): void {
                $message->subject = 'Modified';
            })->toThrow(Error::class);
        });

        it('cannot modify body property', function (): void {
            $message = new Message('Subject', 'Original');

            expect(function () use ($message): void {
                $message->body = 'Modified';
            })->toThrow(Error::class);
        });

        it('cannot modify htmlBody property', function (): void {
            $message = new Message('Subject', 'Body', '<p>Original</p>');

            expect(function () use ($message): void {
                $message->htmlBody = '<p>Modified</p>';
            })->toThrow(Error::class);
        });
    });

    describe('Edge Cases', function (): void {
        it('handles messages with only numbers', function (): void {
            $message = new Message('123', '456', '789');

            expect($message->subject)->toBe('123')
                ->and($message->body)->toBe('456')
                ->and($message->htmlBody)->toBe('789');
        });

        it('handles messages with only special characters', function (): void {
            $message = new Message('!@#$%', '^&*()_+', '<>&"\'');

            expect($message->subject)->toBe('!@#$%')
                ->and($message->body)->toBe('^&*()_+')
                ->and($message->htmlBody)->toBe('<>&"\'');
        });

        it('handles very short valid messages', function (): void {
            $message = new Message('A', 'B', 'C');

            expect($message->subject)->toBe('A')
                ->and($message->body)->toBe('B')
                ->and($message->htmlBody)->toBe('C');
        });

        it('handles messages at exact character limits', function (): void {
            $subject255 = str_repeat('S', 255);
            $body65535 = str_repeat('B', 65535);
            $html65535 = str_repeat('H', 65535);

            $message = new Message($subject255, $body65535, $html65535);

            expect(strlen($message->subject))->toBe(255)
                ->and(strlen($message->body))->toBe(65535)
                ->and(strlen($message->htmlBody))->toBe(65535);
        });

        it('handles mixed content types', function (): void {
            $subject = 'Mixed Content: Text & HTML';
            $plainBody = 'This is plain text with some <tags> that should be preserved.';
            $htmlBody = '<p>This is <strong>actual</strong> HTML content.</p>';

            $message = new Message($subject, $plainBody, $htmlBody);

            expect($message->subject)->toBe($subject)
                ->and($message->body)->toBe($plainBody)
                ->and($message->htmlBody)->toBe($htmlBody)
                ->and($message->hasHtmlBody())->toBeTrue();
        });

        it('handles null checks correctly', function (): void {
            $messageWithoutHtml = new Message('Subject', 'Body');
            $messageWithHtml = new Message('Subject', 'Body', '<p>HTML</p>');

            expect($messageWithoutHtml->htmlBody)->toBeNull()
                ->and($messageWithHtml->htmlBody)->not->toBeNull()
                ->and($messageWithHtml->htmlBody)->toBeString();
        });
    });

    describe('Real-world Usage Scenarios', function (): void {
        it('handles typical email notification format', function (): void {
            $subject = 'New Campaign: Save the Rainforest';
            $plainBody = "A new campaign 'Save the Rainforest' has been created.\n\nView campaign: https://example.com/campaigns/123";
            $htmlBody = '<p>A new campaign <strong>Save the Rainforest</strong> has been created.</p><p><a href="https://example.com/campaigns/123">View campaign</a></p>';

            $message = new Message($subject, $plainBody, $htmlBody);

            expect($message->getPreview(50))->toBe("A new campaign 'Save the Rainforest' has been crea...")
                ->and($message->hasHtmlBody())->toBeTrue()
                ->and((string) $message)->toBe($subject);
        });

        it('handles system alert format', function (): void {
            $subject = 'URGENT: System Maintenance Alert';
            $body = 'System maintenance is scheduled for tonight at 2 AM EST. Expected downtime: 2 hours.';

            $message = new Message($subject, $body);

            expect($message->hasHtmlBody())->toBeFalse()
                ->and($message->getPreview(30))->toBe('System maintenance is schedule...')
                ->and((string) $message)->toContain('URGENT');
        });

        it('handles multilingual content', function (): void {
            $subject = 'Notification: Nouvelle campagne créée';
            $body = 'Une nouvelle campagne a été créée: "Sauver les océans". Détails disponibles sur votre tableau de bord.';

            $message = new Message($subject, $body);

            expect($message->subject)->toContain('Nouvelle')
                ->and($message->body)->toContain('océans')
                ->and($message->getPreview(20))->toBe('Une nouvelle campagn...');
        });

        it('handles donation confirmation format', function (): void {
            $subject = 'Thank you for your donation!';
            $plainBody = "Thank you for your $50.00 donation to 'Environmental Protection Fund'.";
            $htmlBody = '<p>Thank you for your <strong>$50.00</strong> donation to <em>Environmental Protection Fund</em>.</p>';

            $message = new Message($subject, $plainBody, $htmlBody);

            expect($message->equals(new Message($subject, $plainBody, $htmlBody)))->toBeTrue()
                ->and($message->getPreview())->toContain('$50.00')
                ->and($message->getPreview())->not->toContain('<strong>');
        });
    });
});
