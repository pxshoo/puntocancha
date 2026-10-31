<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClubBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_replace_and_remove_own_logo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->put(route('settings.branding.update'), [
            'brand_palette' => 'blue',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect(route('settings.branding.edit'))->assertSessionHasNoErrors();
        $club = $user->club->fresh();
        Storage::disk('local')->assertExists($club->logo_path);
        $this->assertSame('blue', $club->brand_palette);
        $this->assertSame('original', $other->club->fresh()->brand_palette);
        $this->get($club->logoUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
        $old = $club->logo_path;
        $this->put(route('settings.branding.update'), [
            'brand_palette' => 'rose',
            'logo' => UploadedFile::fake()->image('new.jpg', 300, 200),
        ])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        $club->refresh();
        $new = $club->logo_path;
        $this->put(route('settings.branding.update'), ['brand_palette' => 'original', 'remove_logo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($new);
        $this->assertNull($club->fresh()->logo_path);
    }

    public function test_invalid_files_palettes_and_conflicting_actions_are_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('settings.branding.update'), [
            'brand_palette' => 'injected-css',
            'logo' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors(['logo', 'brand_palette']);
        $this->put(route('settings.branding.update'), [
            'brand_palette' => 'blue',
            'logo' => UploadedFile::fake()->image('tiny.png', 20, 20),
        ])->assertSessionHasErrors('logo');
        $this->put(route('settings.branding.update'), [
            'brand_palette' => 'blue',
            'logo' => UploadedFile::fake()->image('valid.png', 100, 100),
            'remove_logo' => 1,
        ])->assertSessionHasErrors('remove_logo');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_staff_and_players_cannot_manage_club_branding(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('settings.index'))->assertForbidden();
        $this->put(route('settings.branding.update'), ['brand_palette' => 'blue'])->assertForbidden();
        $player = User::factory()->create(['role' => 'player', 'club_id' => null]);
        $this->actingAs($player)->get(route('settings.branding.edit'))->assertForbidden();
    }

    public function test_settings_and_branding_forms_render_with_own_club_theme_only(): void
    {
        $user = User::factory()->create();
        $user->club->forceFill(['brand_palette' => 'violet'])->save();
        $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertSee('Logo y colores')->assertSee('Mi plan');
        $this->get(route('settings.branding.edit'))->assertOk()->assertSee('Guardar personalización')->assertSee('--brand-primary: 124 58 237', false);
        $this->get(route('player.explore'))->assertOk()->assertDontSee('data-club-theme', false);
    }

    public function test_public_club_schedule_uses_its_own_palette(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        foreach ([$first, $second] as $owner) {
            $owner->club->forceFill([
                'brand_palette' => $owner->id === $first->id ? 'blue' : 'rose',
                'marketplace_enabled' => true, 'city' => 'Puerto Montt',
                'address' => 'Calle 123', 'phone' => '+56912345678', 'opens_at' => '08:00', 'closes_at' => '23:00',
                'slot_minutes' => 60, 'onboarding_completed_at' => now(),
            ])->save();
            $this->actingAs($owner);
            Court::factory()->create();
        }
        $this->post(route('logout'));
        $this->get(route('player.club', $first->club->slug))->assertOk()
            ->assertSee('--brand-primary: 37 99 235', false)->assertDontSee('--brand-primary: 225 29 72', false);
        $this->get(route('player.club', $second->club->slug))->assertOk()
            ->assertSee('--brand-primary: 225 29 72', false)->assertDontSee('--brand-primary: 37 99 235', false);
    }

    public function test_logo_url_cannot_read_another_clubs_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $version = '11111111-1111-4111-8111-111111111111';
        $path = 'club-logos/'.$other->club_id.'/'.$version.'.png';
        Storage::disk('local')->put($path, 'private');
        $user->club->forceFill(['logo_path' => $path])->save();
        $this->get(route('club.logo', ['club' => $user->club->slug, 'version' => $version]))->assertNotFound();
    }
}
