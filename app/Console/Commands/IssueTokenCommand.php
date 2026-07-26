<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class IssueTokenCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'artisanpack:issue-token
        {--user= : The email address of the user to issue the token to}
        {--name= : A human-readable name for the token}
        {--ability=* : One or more abilities to grant (repeat --ability for each)}';

    /**
     * @var string
     */
    protected $description = 'Mint a Sanctum personal access token for a user and print it once.';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user instanceof User) {
            $this->error('No user found for the provided email.');

            return self::FAILURE;
        }

        $name = $this->resolveName();

        if ($name === '') {
            $this->error('A token name is required.');

            return self::FAILURE;
        }

        $abilities = $this->resolveAbilities();

        if ($abilities === []) {
            $this->error('At least one ability is required.');

            return self::FAILURE;
        }

        $token = $user->createToken($name, $abilities)->plainTextToken;

        $this->info('Token generated. Copy it now — it will not be shown again.');
        $this->newLine();
        $this->line($token);
        $this->newLine();
        $this->line('Issued to: '.$user->email);
        $this->line('Name: '.$name);
        $this->line('Abilities: '.implode(', ', $abilities));

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $email = $this->option('user');

        if (! is_string($email) || $email === '') {
            $email = (string) text(
                label: 'Which user should this token belong to?',
                placeholder: 'admin@example.com',
                required: true,
                validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                    ? 'Please enter a valid email address.'
                    : null,
            );
        }

        return User::query()->where('email', $email)->first();
    }

    private function resolveName(): string
    {
        $name = $this->option('name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return (string) text(
            label: 'Token name',
            placeholder: 'artisanpackui.dev production',
            required: true,
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveAbilities(): array
    {
        /** @var array<int, string> $provided */
        $provided = (array) $this->option('ability');
        $provided = array_values(array_filter($provided, fn ($value): bool => is_string($value) && $value !== ''));

        if ($provided !== []) {
            $invalid = array_values(array_filter($provided, fn (string $value): bool => ! TokenAbility::isAllowed($value)));

            if ($invalid !== []) {
                $this->error('Unknown ability: '.implode(', ', $invalid));
                $this->line('Allowed abilities: '.implode(', ', TokenAbility::values()));

                return [];
            }

            return array_values(array_unique($provided));
        }

        $mode = select(
            label: 'How should abilities be assigned?',
            options: [
                'all' => 'Grant all abilities',
                'choose' => 'Choose specific abilities',
            ],
            default: 'choose',
        );

        if ($mode === 'all') {
            return TokenAbility::values();
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which abilities should this token have?',
            options: array_combine(
                TokenAbility::values(),
                array_map(fn (TokenAbility $ability): string => $ability->label(), TokenAbility::cases()),
            ),
            required: true,
        );

        return array_values($selected);
    }
}
