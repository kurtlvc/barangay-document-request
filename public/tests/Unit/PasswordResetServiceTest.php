<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

require_once __DIR__ . '/../../includes/Enums/PasswordResetStatus.php';
require_once __DIR__ . '/../../includes/DTO/SubmitPasswordResetRequestDTO.php';
require_once __DIR__ . '/../../includes/DTO/AdminResetPasswordDTO.php';
require_once __DIR__ . '/../../includes/Repositories/PasswordResetRepositoryInterface.php';
require_once __DIR__ . '/../../includes/Services/PasswordResetService.php';

use App\DTO\AdminResetPasswordDTO;
use App\DTO\SubmitPasswordResetRequestDTO;
use App\Enums\PasswordResetStatus;
use App\Repositories\PasswordResetRepositoryInterface;
use App\Services\PasswordResetService;
use InvalidArgumentException;
use RuntimeException;

// Minimal standalone test runner for environment without phpunit binary
final class PasswordResetServiceTest
{
    private static int $passed = 0;
    private static int $failed = 0;

    public static function runAll(): void
    {
        echo "Running PasswordResetService unit tests...\n";
        self::testSubmitResetWithInvalidEmail();
        self::testSubmitResetWithExistingUser();
        self::testAdminResetPasswordSuccess();
        self::testAdminResetPasswordShort();

        echo sprintf("\nTests completed. Passed: %d, Failed: %d\n", self::$passed, self::$failed);
        if (self::$failed > 0) {
            exit(1);
        }
    }

    private static function assert(bool $condition, string $testName): void
    {
        if ($condition) {
            self::$passed++;
            echo " [PASS] {$testName}\n";
        } else {
            self::$failed++;
            echo " [FAIL] {$testName}\n";
        }
    }

    public static function testSubmitResetWithInvalidEmail(): void
    {
        $mockRepo = new class implements PasswordResetRepositoryInterface {
            public function findUserById(int $userId): ?array { return null; }
            public function findUserByEmail(string $email): ?array { return null; }
            public function updateUserPassword(int $userId, string $passwordHash): bool { return true; }
            public function createResetRequest(int $userId, ?string $reason): int { return 1; }
            public function findPendingRequestForUser(int $userId): ?array { return null; }
            public function listRequests(?PasswordResetStatus $status = null): array { return []; }
            public function findRequestById(int $requestId): ?array { return null; }
            public function updateRequestStatus(int $requestId, PasswordResetStatus $status, int $resolvedBy, ?string $adminNotes = null): bool { return true; }
        };

        $service = new PasswordResetService($mockRepo);
        $threw = false;
        try {
            $service->submitResetRequest(new SubmitPasswordResetRequestDTO('invalid-email'));
        } catch (InvalidArgumentException) {
            $threw = true;
        }

        self::assert($threw, 'testSubmitResetWithInvalidEmail throws InvalidArgumentException');
    }

    public static function testSubmitResetWithExistingUser(): void
    {
        $mockRepo = new class implements PasswordResetRepositoryInterface {
            public function findUserById(int $userId): ?array { return null; }
            public function findUserByEmail(string $email): ?array {
                return ['id' => 99, 'name' => 'Test Resident', 'email' => $email, 'role' => 'resident', 'is_active' => 1];
            }
            public function updateUserPassword(int $userId, string $passwordHash): bool { return true; }
            public function createResetRequest(int $userId, ?string $reason): int { return 42; }
            public function findPendingRequestForUser(int $userId): ?array { return null; }
            public function listRequests(?PasswordResetStatus $status = null): array { return []; }
            public function findRequestById(int $requestId): ?array { return null; }
            public function updateRequestStatus(int $requestId, PasswordResetStatus $status, int $resolvedBy, ?string $adminNotes = null): bool { return true; }
        };

        $service = new PasswordResetService($mockRepo);
        $result = $service->submitResetRequest(new SubmitPasswordResetRequestDTO('resident@test.com', 'Forgot answer'));

        self::assert($result['status'] === 'ok' && $result['request_id'] === 42, 'testSubmitResetWithExistingUser queues request');
    }

    public static function testAdminResetPasswordSuccess(): void
    {
        $mockRepo = new class implements PasswordResetRepositoryInterface {
            public ?string $lastSavedHash = null;
            public function findUserById(int $userId): ?array {
                return ['id' => 5, 'name' => 'Maria Santos', 'email' => 'maria@test.com'];
            }
            public function findUserByEmail(string $email): ?array { return null; }
            public function updateUserPassword(int $userId, string $passwordHash): bool {
                $this->lastSavedHash = $passwordHash;
                return true;
            }
            public function createResetRequest(int $userId, ?string $reason): int { return 1; }
            public function findPendingRequestForUser(int $userId): ?array { return null; }
            public function listRequests(?PasswordResetStatus $status = null): array { return []; }
            public function findRequestById(int $requestId): ?array { return null; }
            public function updateRequestStatus(int $requestId, PasswordResetStatus $status, int $resolvedBy, ?string $adminNotes = null): bool { return true; }
        };

        $service = new PasswordResetService($mockRepo);
        $dto = new AdminResetPasswordDTO(5, 'NewStrongPassword123!', 10);
        $result = $service->adminResetPassword($dto, 1);

        self::assert(
            $result['status'] === 'ok' && $mockRepo->lastSavedHash !== null && password_verify('NewStrongPassword123!', $mockRepo->lastSavedHash),
            'testAdminResetPasswordSuccess hashes and saves password'
        );
    }

    public static function testAdminResetPasswordShort(): void
    {
        $mockRepo = new class implements PasswordResetRepositoryInterface {
            public function findUserById(int $userId): ?array {
                return ['id' => 5, 'name' => 'Maria Santos'];
            }
            public function findUserByEmail(string $email): ?array { return null; }
            public function updateUserPassword(int $userId, string $passwordHash): bool { return true; }
            public function createResetRequest(int $userId, ?string $reason): int { return 1; }
            public function findPendingRequestForUser(int $userId): ?array { return null; }
            public function listRequests(?PasswordResetStatus $status = null): array { return []; }
            public function findRequestById(int $requestId): ?array { return null; }
            public function updateRequestStatus(int $requestId, PasswordResetStatus $status, int $resolvedBy, ?string $adminNotes = null): bool { return true; }
        };

        $service = new PasswordResetService($mockRepo);
        $dto = new AdminResetPasswordDTO(5, 'short');
        $threw = false;
        try {
            $service->adminResetPassword($dto, 1);
        } catch (InvalidArgumentException) {
            $threw = true;
        }

        self::assert($threw, 'testAdminResetPasswordShort rejects password under 8 chars');
    }
}

PasswordResetServiceTest::runAll();
