<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\DailyChallengeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\QuestionReportController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionWorkflowController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\FriendChallengeController;
use App\Http\Controllers\DuelController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use App\Http\Controllers\SuperAdmin\TaxonomyManagementController;

Route::prefix('v1')->group(function () {

    // AUTH PUBLIC
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/auth/guest', [AuthController::class, 'guest']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/confirm-parental-consent', [AuthController::class, 'confirmParentalConsent']);
    Route::get('/countries', [CountryController::class, 'index']);

    // AUTH PROTEGE
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);
        Route::get('/me/export', [AuthController::class, 'exportData']);
        Route::delete('/me', [AuthController::class, 'deleteAccount']);
        Route::patch('/me/profile', [ProfileController::class, 'update']);
        Route::get('/me/progress', function (Request $request) {
            return response()->json(['data' => $request->user()->load('profile')]);
        });
    });

    // QUIZ
    Route::post('/quiz/sessions', [QuizController::class, 'start']);
    Route::post('/quiz/sessions/{id}/answers', [QuizController::class, 'answer'])->middleware(['throttle:quiz-answer', 'idempotent']);
    Route::post('/quiz/sessions/{id}/finish', [QuizController::class, 'finish'])->middleware('idempotent');

    // DEFI DU JOUR
    Route::get('/daily-challenge', [DailyChallengeController::class, 'show']);
    Route::post('/daily-challenge/start', [DailyChallengeController::class, 'start']);

    // CLASSEMENTS
    Route::get('/leaderboards', [LeaderboardController::class, 'index']);

    // SIGNALEMENTS
    Route::post('/questions/{id}/reports', [QuestionReportController::class, 'store']);

    // REVISION (SRS)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/review/queue', [RevisionController::class, 'queue']);
        Route::post('/review/sessions', [RevisionController::class, 'start']);
        Route::post('/review/sessions/{id}/answers', [RevisionController::class, 'answer']);
    });

    // DEFIS AMIS
    Route::middleware('auth:sanctum')->post('/challenges', [FriendChallengeController::class, 'create']);
    Route::get('/challenges/{code}', [FriendChallengeController::class, 'show']);
    Route::post('/challenges/{code}/play', [FriendChallengeController::class, 'play']);

    // DUELS
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/duels', [DuelController::class, 'index']);
        Route::post('/duels', [DuelController::class, 'create']);
        Route::post('/duels/{id}/join', [DuelController::class, 'join']);
        Route::post('/duels/{id}/play', [DuelController::class, 'play']);
    });

    // LIGUES
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/leagues/current', [LeagueController::class, 'current']);
        Route::get('/leagues/history', [LeagueController::class, 'history']);
    });

    // PUSH
    Route::get('/push/vapid-key', [PushController::class, 'vapidKey']);
    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
    Route::delete('/push/unsubscribe', [PushController::class, 'unsubscribe']);
    Route::middleware('auth:sanctum')->post('/push/test', [PushController::class, 'test']);

    // ADMIN
    Route::prefix('admin')->group(function () {

        Route::post('/auth/login', [AdminAuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/auth/2fa/setup', [AdminAuthController::class, 'setup2fa']);
            Route::post('/auth/2fa/confirm', [AdminAuthController::class, 'confirm2fa']);
            Route::post('/auth/2fa/verify', [AdminAuthController::class, 'verify2fa']);
        });

        Route::middleware('admin')->group(function () {

            Route::get('/auth/me', [AdminAuthController::class, 'me']);
            Route::post('/auth/logout', [AdminAuthController::class, 'logout']);

            // Imports
            Route::middleware('admin.permission:content.manage')->group(function () {
                Route::get('/imports', [ImportController::class, 'index']);
                Route::post('/imports', [ImportController::class, 'store']);
                Route::get('/imports/{id}', [ImportController::class, 'show']);
                Route::post('/imports/{id}/confirm', [ImportController::class, 'confirm']);
                Route::delete('/imports/{id}', [ImportController::class, 'cancel']);
                Route::get('/imports/{id}/errors', [ImportController::class, 'errorsReport']);

                // Media
                Route::get('/media', [MediaController::class, 'index']);
                Route::post('/media/upload', [MediaController::class, 'upload']);
                Route::delete('/media/{id}', [MediaController::class, 'destroy']);
            });

            // Questions
            Route::get('/questions', [QuestionWorkflowController::class, 'index']);
            Route::get('/questions/{id}', [QuestionWorkflowController::class, 'show']);
            Route::post('/questions/{id}/transition', [QuestionWorkflowController::class, 'transition'])->middleware('admin.permission:content.workflow');
            Route::get('/questions/{id}/versions', [QuestionWorkflowController::class, 'versions']);

            // Reports
            Route::middleware('admin.permission:content.review')->group(function () {
                Route::get('/reports', [AdminReportController::class, 'index']);
                Route::get('/reports/{id}', [AdminReportController::class, 'show']);
                Route::post('/reports/{id}/resolve', [AdminReportController::class, 'resolve']);
            });

            // Audit
            Route::get('/audit-logs', [QuestionWorkflowController::class, 'auditLogs'])->middleware('admin.permission:admin.manage');

            // SUPER
            Route::prefix('super')->group(function () {
                Route::middleware('admin.permission:admin.manage')->group(function () {
                    Route::get('/dashboard/overview', [SuperAdminDashboardController::class, 'overview']);
                    Route::get('/dashboard/realtime', [SuperAdminDashboardController::class, 'realtime']);
                    Route::get('/dashboard/top-players', [SuperAdminDashboardController::class, 'topPlayers']);
                    Route::get('/dashboard/top-reported', [SuperAdminDashboardController::class, 'topReportedQuestions']);
                    Route::get('/dashboard/activity', [SuperAdminDashboardController::class, 'recentActivity']);

                    Route::get('/users', [UserManagementController::class, 'index']);
                    Route::get('/users/{id}', [UserManagementController::class, 'show']);
                    Route::patch('/users/{id}', [UserManagementController::class, 'update']);
                    Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);
                    Route::post('/users/{id}/ban', [UserManagementController::class, 'ban']);
                    Route::post('/users/{id}/unban', [UserManagementController::class, 'unban']);
                    Route::post('/users/{id}/force-verify', [UserManagementController::class, 'forceVerifyEmail']);
                    Route::post('/users/{id}/impersonate', [UserManagementController::class, 'impersonate']);

                    Route::middleware('admin.permission:super.manage')->group(function () {
                        Route::get('/admins', [UserManagementController::class, 'listAdmins']);
                        Route::post('/admins', [UserManagementController::class, 'createAdmin']);
                        Route::patch('/admins/{id}', [UserManagementController::class, 'updateAdmin']);
                        Route::delete('/admins/{id}', [UserManagementController::class, 'deleteAdmin']);
                    });

                    Route::get('/badges', [TaxonomyManagementController::class, 'listBadges']);
                    Route::post('/badges', [TaxonomyManagementController::class, 'createBadge']);
                    Route::patch('/badges/{id}', [TaxonomyManagementController::class, 'updateBadge']);
                    Route::delete('/badges/{id}', [TaxonomyManagementController::class, 'deleteBadge']);

                    Route::get('/categories', [TaxonomyManagementController::class, 'listCategories']);
                    Route::post('/categories', [TaxonomyManagementController::class, 'createCategory']);
                    Route::patch('/categories/{id}', [TaxonomyManagementController::class, 'updateCategory']);
                    Route::delete('/categories/{id}', [TaxonomyManagementController::class, 'deleteCategory']);

                    Route::get('/countries', [TaxonomyManagementController::class, 'listCountries']);
                    Route::patch('/countries/{id}', [TaxonomyManagementController::class, 'updateCountry']);
                    Route::get('/regions', [TaxonomyManagementController::class, 'listRegions']);
                });
            });
        });
    });
});
