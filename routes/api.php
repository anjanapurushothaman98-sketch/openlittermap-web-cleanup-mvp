<?php

use App\Http\Controllers\Achievements\AchievementsController;
use App\Http\Controllers\Admin\AdminImpersonateController;
use App\Http\Controllers\Admin\AdminQueueController;
use App\Http\Controllers\Admin\AdminResetTagsController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\FindPhotoByIdController;
use App\Http\Controllers\Admin\GetNextImageToVerifyController;
use App\Http\Controllers\Admin\GoBackOnePhotoController;
use App\Http\Controllers\Admin\UpdateTagsController;
use App\Http\Controllers\Admin\VerifyImageWithTagsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\API\DeleteAccountController;
use App\Http\Controllers\API\GlobalStatsController;
use App\Http\Controllers\API\MobileAppVersionController;
use App\Http\Controllers\API\Tags\GetTagsController;
use App\Http\Controllers\API\Tags\PhotoTagsController;
use App\Http\Controllers\API\QuickTagsController;
use App\Http\Controllers\API\TeamsController as APITeamsController;
use App\Http\Controllers\ApiSettingsController;
use App\Http\Controllers\Auth\AuthTokenController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Bbox\BoundingBoxController;
use App\Http\Controllers\Bbox\VerifyBoxController;
use App\Http\Controllers\Cleanups\CreateCleanupController;
use App\Http\Controllers\Cleanups\GetCleanupsGeoJsonController;
use App\Http\Controllers\Cleanups\JoinCleanupController;
use App\Http\Controllers\Cleanups\LeaveCleanupController;
use App\Http\Controllers\Clusters\ClusterController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DisplayTagsOnMapController;
use App\Http\Controllers\DownloadControllerNew;
use App\Http\Controllers\EmailSubController;
use App\Http\Controllers\Leaderboard\LeaderboardController;
use App\Http\Controllers\Littercoin\Merchants\ApproveMerchantController;
use App\Http\Controllers\Littercoin\Merchants\DeleteMerchantController;
use App\Http\Controllers\Location\GetListOfCountriesController;
use App\Http\Controllers\Location\LocationController;
use App\Http\Controllers\Location\TagController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\Maps\Search\FindCustomTagsController;
use App\Http\Controllers\Merchants\BecomeAMerchantController;
use App\Http\Controllers\Photos\PhotoSignedUrlController;
use App\Http\Controllers\PhotosController;
use App\Http\Controllers\Points\PointsController;
use App\Http\Controllers\Points\PointsStatsController;
use App\Http\Controllers\RedisDataController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Teams\ParticipantController;
use App\Http\Controllers\Teams\ParticipantPhotoController;
use App\Http\Controllers\Teams\ParticipantSessionController;
use App\Http\Controllers\Teams\TeamsClusterController;
use App\Http\Controllers\Teams\TeamsController;
use App\Http\Controllers\Teams\TeamsDataController;
use App\Http\Controllers\Teams\TeamsLeaderboardController;
use App\Http\Controllers\Teams\TeamsSettingsController;
use App\Http\Controllers\Teams\TeamPhotosController;
use App\Http\Controllers\Uploads\UploadPhotoController;
use App\Http\Controllers\User\Photos\UsersUploadsController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserPhotoController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorldCup\GetDataForWorldCupController;
use Illuminate\Support\Facades\Route;

// ⭐ REQUIRED FOR YOUR MARKER ROUTES
use Illuminate\Http\Request;
use App\Models\Marker;
use App\Models\Area;
use App\Models\Bin;

/*
|--------------------------------------------------------------------------
| v3 — OLM v5 API
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'v3', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/upload', UploadPhotoController::class);
    Route::post('/tags', [PhotoTagsController::class, 'store']);
    Route::put('/tags', [PhotoTagsController::class, 'update']);
    Route::get('/user/photos', [UsersUploadsController::class, 'index']);
    Route::get('/user/photos/stats', [UsersUploadsController::class, 'stats']);
    Route::get('/user/photos/locations', [UsersUploadsController::class, 'locations']);
    Route::patch('/photos/{photo}/visibility', [UsersUploadsController::class, 'toggleVisibility']);
    Route::get('/user/quick-tags', [QuickTagsController::class, 'index']);
    Route::put('/user/quick-tags', [QuickTagsController::class, 'update']);
    Route::get('/user/top-tags', [QuickTagsController::class, 'topTags']);
});

/*
|--------------------------------------------------------------------------
| Public (no auth)
|--------------------------------------------------------------------------
*/

Route::get('/tags', [GetTagsController::class, 'index']);
Route::get('/tags/all', [GetTagsController::class, 'getAllTags']);
Route::get('/points', [PointsController::class, 'index']);
Route::get('/points/stats', [PointsStatsController::class, 'index']);
Route::get('/points/{id}', [PointsController::class, 'show'])->where('id', '[0-9]+');
Route::get('/photos/{id}/signed-url', PhotoSignedUrlController::class)
    ->where('id', '[0-9]+')
    ->middleware('throttle:60,1');
Route::get('/global/stats-data', [GlobalStatsController::class, 'index']);
Route::get('/mobile-app-version', MobileAppVersionController::class);
Route::get('/levels', fn () => response()->json(config('levels.thresholds')));

/*
|--------------------------------------------------------------------------
| Locations
|--------------------------------------------------------------------------
*/

Route::get('/locations/global', [LocationController::class, 'global']);
Route::get('/locations/world-cup', GetDataForWorldCupController::class);
Route::get('/locations/{type}', [LocationController::class, 'index']);
Route::get('/locations/{type}/{id}', [LocationController::class, 'show']);

Route::prefix('locations/{type}/{id}/tags')->group(function () {
    Route::get('/top', [TagController::class, 'top']);
    Route::get('/summary', [TagController::class, 'summary']);
    Route::get('/by-category', [TagController::class, 'byCategory']);
    Route::get('/cleanup', [TagController::class, 'cleanup']);
    Route::get('/trending', [TagController::class, 'trending']);
});

Route::prefix('clusters')->group(function () {
    Route::get('/', [ClusterController::class, 'index']);
    Route::get('/zoom-levels', [ClusterController::class, 'zoomLevels']);
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::post('/auth/register', [RegisterController::class, 'register']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:3,1');
Route::post('/password/validate-token', [ResetPasswordController::class, 'validateToken']);
Route::post('/password/reset', [ResetPasswordController::class, 'reset']);

Route::post('/auth/login', [LoginController::class, 'login'])
    ->middleware(['web']);

Route::post('/auth/token', [AuthTokenController::class, 'login'])
    ->middleware('throttle:10,1');

Route::post('/auth/logout', [LoginController::class, 'logout'])
    ->middleware(['web', 'auth:web']);

Route::post('/validate-token', function (Request $request) {
    return ['message' => 'valid'];
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| User Profile & Photos
|--------------------------------------------------------------------------
*/

Route::get('/user/profile/{id}', [ProfileController::class, 'show'])->where('id', '[0-9]+');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile/index', [ProfileController::class, 'index']);
    Route::get('/user/profile/refresh', [ProfileController::class, 'refresh']);
    Route::get('/user/profile/map', [ProfileController::class, 'geojson']);
    Route::get('/user/profile/download', [ProfileController::class, 'download'])->middleware('throttle:csv-export');
    Route::get('/user/profile/photos/index', [UserPhotoController::class, 'index']);
    Route::get('/user/profile/photos/filter', [UserPhotoController::class, 'filter']);
    Route::post('/user/profile/photos/tags/bulkTag', [UserPhotoController::class, 'bulkTag']);
    Route::post('/user/profile/photos/delete', [UserPhotoController::class, 'destroy']);
    Route::post('/profile/upload-profile-photo', [UsersController::class, 'uploadProfilePhoto']);
    Route::post('/profile/photos/delete', [PhotosController::class, 'deleteImage']);

    Route::post('/user/onboarding/skip', function () {
        $user = auth()->user();
        if ($user->onboarding_completed_at === null) {
            $user->update(['onboarding_completed_at' => now()]);
        }
        return response()->json(['success' => true]);
    });
});

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/settings/details', [UsersController::class, 'details']);
    Route::patch('/settings/details/password', [UsersController::class, 'changePassword']);
    Route::post('/settings/privacy/update', [UsersController::class, 'togglePrivacy']);
    Route::post('/settings/phone/submit', [UsersController::class, 'phone']);
    Route::post('/settings/phone/remove', [UsersController::class, 'removePhone']);
    Route::post('/settings/toggle', [UsersController::class, 'togglePresence']);
    Route::post('/settings/email/toggle', [EmailSubController::class, 'toggleEmailSub']);
    Route::get('/settings/flags/countries', [SettingsController::class, 'getCountries']);
    Route::post('/settings/save-flag', [SettingsController::class, 'saveFlag']);
    Route::patch('/settings', [SettingsController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/settings/privacy/maps/name', [ApiSettingsController::class, 'mapsName']);
    Route::post('/settings/privacy/maps/username', [ApiSettingsController::class, 'mapsUsername']);
    Route::post('/settings/privacy/leaderboard/name', [ApiSettingsController::class, 'leaderboardName']);
    Route::post('/settings/privacy/leaderboard/username', [ApiSettingsController::class, 'leaderboardUsername']);
    Route::post('/settings/privacy/createdby/name', [ApiSettingsController::class, 'createdByName']);
    Route::post('/settings/privacy/createdby/username', [ApiSettingsController::class, 'createdByUsername']);
    Route::post('/settings/update', [ApiSettingsController::class, 'update']);
    Route::post('/settings/privacy/toggle-previous-tags', [ApiSettingsController::class, 'togglePreviousTags']);
    Route::post('/settings/delete-account', DeleteAccountController::class);
});

/*
|--------------------------------------------------------------------------
| Teams
|--------------------------------------------------------------------------
*/

Route::prefix('/teams')->group(function () {
    Route::get('/types', [APITeamsController::class, 'types']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/members', [APITeamsController::class, 'members']);
        Route::get('/leaderboard', [TeamsLeaderboardController::class, 'index']);
        Route::get('/list', [APITeamsController::class, 'list']);
        Route::get('/data', [TeamsDataController::class, 'index']);
        Route::get('/clusters/{team}', [TeamsClusterController::class, 'clusters']);
        Route::get('/points/{team}', [TeamsClusterController::class, 'points']);
        Route::get('/joined', [TeamsController::class, 'joined']);
        Route::patch('/update/{team}', [APITeamsController::class, 'update']);
        Route::post('/active', [APITeamsController::class, 'setActiveTeam']);
        Route::post('/create', [APITeamsController::class, 'create']);
        Route::post('/download', [APITeamsController::class, 'download'])->middleware('throttle:csv-export');
        Route::post('/inactivate', [APITeamsController::class, 'inactivateTeams']);
        Route::post('/join', [APITeamsController::class, 'join']);
        Route::post('/leave', [APITeamsController::class, 'leave']);
        Route::post('/leaderboard/visibility', [TeamsLeaderboardController::class, 'toggle']);
        Route::post('/settings', [TeamsSettingsController::class, 'index']);

        Route::prefix('/photos')->group(function () {
            Route::get('/', [TeamPhotosController::class, 'index']);
            Route::get('/map', [TeamPhotosController::class, 'mapPoints']);
            Route::get('/member-stats', [TeamPhotosController::class, 'memberStats']);
            Route::get('/{photo}', [TeamPhotosController::class, 'show']);
            Route::patch('/{photo}/tags', [TeamPhotosController::class, 'updateTags']);
            Route::post('/approve', [TeamPhotosController::class, 'approve']);
            Route::post('/revoke', [TeamPhotosController::class, 'revoke']);
            Route::delete('/{photo}', [TeamPhotosController::class, 'destroy']);
        });

        Route::prefix('/{team}/participants')->group(function () {
            Route::get('/', [ParticipantController::class, 'index']);
            Route::post('/', [ParticipantController::class, 'store']);
            Route::post('/{participant}/deactivate', [ParticipantController::class, 'deactivate']);
            Route::post('/{participant}/activate', [ParticipantController::class, 'activate']);
            Route::post('/{participant}/reset-token', [ParticipantController::class, 'resetToken']);
            Route::delete('/{participant}', [ParticipantController::class, 'destroy']);
        });
    });
});

Route::post('/participant/session', [ParticipantSessionController::class, 'enter']);

Route::prefix('/participant')->middleware('participant')->group(function () {
    Route::post('/upload', UploadPhotoController::class);
    Route::post('/tags', [PhotoTagsController::class, 'store']);
    Route::get('/photos', [ParticipantPhotoController::class, 'index']);
    Route::delete('/photos/{photo}', [ParticipantPhotoController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Leaderboard
|--------------------------------------------------------------------------
*/

Route::get('/leaderboard', LeaderboardController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/achievements', [AchievementsController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/redis-data', [RedisDataController::class, 'index']);
    Route::get('/redis-data/performance', [RedisDataController::class, 'performance']);
    Route::get('/redis-data/key-analysis', [RedisDataController::class, 'keyAnalysis']);
    Route::get('/redis-data/{userId}', [RedisDataController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Global map
|--------------------------------------------------------------------------
*/

Route::get('/global/search/custom-tags', FindCustomTagsController::class);

/*
|--------------------------------------------------------------------------
| Community & Map data
|--------------------------------------------------------------------------
*/

Route::get('/community/stats', [CommunityController::class, 'stats']);
Route::get('/tags-search', [DisplayTagsOnMapController::class, 'show']);
Route::get('/city', [MapController::class, 'getCity']);
Route::get('/countries/names', GetListOfCountriesController::class);

/*
|--------------------------------------------------------------------------
| Cleanups
|--------------------------------------------------------------------------
*/

Route::post('/cleanups/create', CreateCleanupController::class);
Route::get('/cleanups/get-cleanups', GetCleanupsGeoJsonController::class);
Route::post('/cleanups/{inviteLink}/join', JoinCleanupController::class);
Route::post('/cleanups/{inviteLink}/leave', LeaveCleanupController::class);

/*
|--------------------------------------------------------------------------
| Downloads
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/download', [DownloadControllerNew::class, 'index'])->middleware('throttle:csv-export');
});

/*
|--------------------------------------------------------------------------
| Littercoin
|--------------------------------------------------------------------------
*/

Route::post('/littercoin/merchants', BecomeAMerchantController::class);

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => '/admin', 'middleware' => 'admin'], function () {
    Route::get('/photos', AdminQueueController::class);
    Route::get('/find-photo-by-id', FindPhotoByIdController::class);
    Route::get('/get-next-image-to-verify', GetNextImageToVerifyController::class);
    Route::get('/get-countries-with-photos', [AdminController::class, 'getCountriesWithPhotos']);
    Route::get('/go-back-one', GoBackOnePhotoController::class);
    Route::post('/verify', [AdminController::class, 'verify']);
    Route::post('/verify-tags-as-correct', VerifyImageWithTagsController::class);
    Route::post('/reset-tags', AdminResetTagsController::class);
    Route::post('/contentsupdatedelete', [AdminController::class, 'updateDelete']);
    Route::post('/update-tags', UpdateTagsController::class);
    Route::post('/destroy', [AdminController::class, 'destroy']);
    Route::post('/merchants/approve', ApproveMerchantController::class);
    Route::post('/merchants/delete', DeleteMerchantController::class);

    Route::get('/stats', AdminStatsController::class);

    Route::get('/users', [AdminUsersController::class, 'index']);
    Route::post('/users/{user}/trust', [AdminUsersController::class, 'trust']);
    Route::post('/users/{user}/approve-all', [AdminUsersController::class, 'approveAll']);
    Route::post('/users/{user}/school-manager', [AdminUsersController::class, 'toggleSchoolManager']);
    Route::patch('/users/{user}/username', [AdminUsersController::class, 'updateUsername']);

    Route::post('/users/{user}/impersonate', [AdminImpersonateController::class, 'start']);
});

Route::post('/impersonate/stop', [AdminImpersonateController::class, 'stop'])
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Bbox
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => '/bbox', 'middleware' => ['can_bbox']], function () {
    Route::get('/index', [BoundingBoxController::class, 'index']);
    Route::post('/create', [BoundingBoxController::class, 'create']);
    Route::post('/skip', [BoundingBoxController::class, 'skip']);
    Route::post('/tags/update', [BoundingBoxController::class, 'updateTags']);
    Route::post('/tags/wrong', [BoundingBoxController::class, 'wrongTags']);
    Route::get('/verify/index', [VerifyBoxController::class, 'index']);
    Route::post('/verify/update', [VerifyBoxController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Custom Marker API (No CSRF, No Auth)
|--------------------------------------------------------------------------
|
| These routes are stateless and safe for fetch('/api/markers').
| DO NOT re-import Request here — it is already imported at the top.
|
*/



Route::middleware('api')->group(function () {

    // Save marker
Route::post('/markers', function (Request $request) {
    $photoPath = null;

    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('markers', 'public');
    }

    $user = auth()->user();

    return Marker::create([
        'lat' => $request->lat,
        'lng' => $request->lng,
        'status' => $request->status,
        'description' => $request->description,
        'photo' => $photoPath,
        'creator' => $user ? ($user->name ?? $user->username) : 'Anonymous',
        'litter_type' => $request->litter_type,
        'weight_kg' => $request->weight_kg,
    ]);
});
    // Get all markers
    Route::get('/markers', function () {
        return Marker::all();
    });

    // Save area (polygon)
    Route::post('/areas', function (Request $request) {
    $user = auth()->user();

    return Area::create([
        'coordinates' => json_encode($request->coordinates),
        'status' => $request->status,
        'description' => $request->description,
        'creator' => $user ? ($user->name ?? $user->username) : 'Anonymous',
    ]);
});

    // Get all areas
    Route::get('/areas', function () {
        return Area::all();
    });

    // Update an area
    Route::patch('/areas/{id}', function (Request $request, $id) {
        $area = Area::findOrFail($id);

        $area->update([
            'status' => $request->status ?? $area->status,
            'description' => $request->description ?? $area->description,
        ]);

        return $area;
    });

    // Delete an area
    Route::delete('/areas/{id}', function ($id) {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json(['success' => true]);
    });

    // Save bin
    Route::post('/bins', function (Request $request) {
    $user = auth()->user();

    return Bin::create([
        'lat' => $request->lat,
        'lng' => $request->lng,
        'status' => $request->status,
        'description' => $request->description,
        'creator' => $user ? ($user->name ?? $user->username) : 'Anonymous',
    ]);
});

    // Get all bins
    Route::get('/bins', function () {
        return Bin::all();
    });

    // Update a bin
    Route::patch('/bins/{id}', function (Request $request, $id) {
        $bin = Bin::findOrFail($id);

        $bin->update([
            'status' => $request->status ?? $bin->status,
            'description' => $request->description ?? $bin->description,
        ]);

        return $bin;
    });

    // Delete a bin
    Route::delete('/bins/{id}', function ($id) {
        $bin = Bin::findOrFail($id);
        $bin->delete();

        return response()->json(['success' => true]);
    });

    // Get current logged-in user's profile
    Route::get('/profile', function (Request $request) {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'Not logged in'], 401);
        }

        return response()->json($user);
    });

    // Update current logged-in user's profile
    Route::post('/profile/update', function (Request $request) {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'Not logged in'], 401);
        }

        $avatarPath = $user->avatar;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update([
            'name' => $request->name ?? $user->name,
            'age' => $request->age ?? $user->age,
            'avatar' => $avatarPath,
        ]);

        return response()->json($user);
    });

});