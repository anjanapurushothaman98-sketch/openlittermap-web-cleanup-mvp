<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('picked_up')->nullable()->default(true);
            $table->string('sub_token')->nullable();
            $table->string('stripe_id')->nullable();
            $table->boolean('emailsub')->default(1);
            $table->string('eth_wallet')->nullable();
            $table->integer('littercoin_allowance')->default(0);
            $table->unsignedInteger('active_team')->nullable();
            $table->boolean('verification_required')->default(0);
            $table->boolean('username_flagged')->default(0);
            $table->boolean('prevent_others_tagging_my_photos')->default(0);
            $table->integer('littercoin_owed')->default(0);
            $table->integer('littercoin_paid')->default(0);
            $table->boolean('previous_tags')->default(0);
            $table->integer('remaining_teams')->default(1);
            $table->integer('bbox_verification_count')->default(0);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->text('settings')->nullable();
            $table->boolean('public_profile')->default(1);
            $table->boolean('public_photos')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'picked_up', 'sub_token', 'stripe_id', 'emailsub', 'eth_wallet',
                'littercoin_allowance', 'active_team', 'verification_required',
                'username_flagged', 'prevent_others_tagging_my_photos',
                'littercoin_owed', 'littercoin_paid', 'previous_tags',
                'remaining_teams', 'bbox_verification_count',
                'onboarding_completed_at', 'settings', 'public_profile', 'public_photos',
            ]);
        });
    }
};
