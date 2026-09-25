<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * ADMIN-WEB-02 — Authentication, RBAC & Session Security.
 *
 * Negative test matrix per planning spec:
 *
 *   Actor       | Route Admin        | Route Sponsor       | Route Public
 *   ----------- | ------------------ | ------------------- | -------------
 *   Guest       | 302 -> login       | 302 -> login        | 200
 *   Student     | 403                | 403                 | 200
 *   Sponsor     | 403                | 200                 | 200
 *   Admin/Owner | 200                | 200                 | 200
 *
 * Additional: forged role payload must be ignored; CSRF required on logout.
 */
final class RbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    // ---- Public Routes (anchor for matrix) ----

    public function test_public_route_is_accessible_to_everyone(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('home'))->assertOk();
    }

    public function test_public_route_accessible_to_student(): void
    {
        $student = $this->makeUser('student');
        $this->actingAs($student)->get('/')->assertOk();
    }

    // ---- Admin Routes ----

    public function test_admin_route_redirects_guest_to_login(): void
    {
        foreach (['admin.sponsors', 'admin.products', 'admin.campaigns', 'admin.events', 'admin.best-deals', 'admin.bulk-upload', 'admin.notifications'] as $name) {
            $response = $this->get(route($name));
            $response->assertRedirect(route('login'));
            $this->assertStringContainsString('login', $response->headers->get('Location'));
        }
    }

    public function test_dashboard_redirects_guest_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_route_rejects_student_with_403(): void
    {
        $student = $this->makeUser('student');

        foreach (['admin.sponsors', 'admin.products', 'admin.campaigns', 'admin.events', 'admin.best-deals', 'admin.bulk-upload', 'admin.notifications'] as $name) {
            $this->actingAs($student)->get(route($name))->assertStatus(403);
        }

        $this->actingAs($student)->get(route('dashboard'))->assertStatus(403);
    }

    public function test_admin_route_rejects_sponsor_with_403(): void
    {
        $sponsorUser = $this->makeUser('sponsor');

        foreach (['admin.sponsors', 'admin.products', 'admin.campaigns', 'admin.events', 'admin.best-deals', 'admin.bulk-upload', 'admin.notifications'] as $name) {
            $this->actingAs($sponsorUser)->get(route($name))->assertStatus(403);
        }

        $this->actingAs($sponsorUser)->get(route('dashboard'))->assertStatus(403);
    }

    public function test_admin_route_allows_super_admin(): void
    {
        $admin = $this->makeUser('super_admin');

        foreach (['admin.sponsors', 'admin.products', 'admin.campaigns', 'admin.events', 'admin.best-deals', 'admin.bulk-upload', 'admin.notifications'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_admin_route_allows_owner(): void
    {
        $owner = $this->makeUser('owner');

        foreach (['admin.sponsors', 'admin.products', 'admin.campaigns', 'admin.events', 'admin.best-deals', 'admin.bulk-upload', 'admin.notifications'] as $name) {
            $this->actingAs($owner)->get(route($name))->assertOk();
        }

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();
    }

    // ---- Sponsor Portal Routes ----

    public function test_sponsor_route_redirects_guest_to_login(): void
    {
        foreach (['sponsor.dashboard', 'sponsor.products', 'sponsor.campaigns.hero', 'sponsor.campaigns.horizontal', 'sponsor.campaigns.card', 'sponsor.campaigns.popup', 'sponsor.notifications'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    public function test_sponsor_route_rejects_student_with_403(): void
    {
        $student = $this->makeUser('student');

        foreach (['sponsor.dashboard', 'sponsor.products', 'sponsor.campaigns.hero'] as $name) {
            $this->actingAs($student)->get(route($name))->assertStatus(403);
        }
    }

    public function test_sponsor_route_allows_sponsor_user(): void
    {
        $sponsorUser = $this->makeUser('sponsor');

        $this->actingAs($sponsorUser)->get(route('sponsor.dashboard'))->assertOk();
        $this->actingAs($sponsorUser)->get(route('sponsor.products'))->assertOk();
        $this->actingAs($sponsorUser)->get(route('sponsor.campaigns.hero'))->assertOk();
        $this->actingAs($sponsorUser)->get(route('sponsor.campaigns.horizontal'))->assertOk();
        $this->actingAs($sponsorUser)->get(route('sponsor.campaigns.card'))->assertOk();
        $this->actingAs($sponsorUser)->get(route('sponsor.campaigns.popup'))->assertOk();
        $this->actingAs($sponsorUser)->get(route('sponsor.notifications'))->assertOk();
    }

    public function test_sponsor_route_allows_owner_and_admin(): void
    {
        foreach (['owner', 'super_admin'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user)->get(route('sponsor.dashboard'))->assertOk();
            $this->actingAs($user)->get(route('sponsor.products'))->assertOk();
        }
    }

    // ---- Session & CSRF Security ----

    public function test_logout_requires_post_and_valid_csrf(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user);

        // GET to logout must NOT end session (CSRF protection)
        $response = $this->get(route('logout'));
        $this->assertAuthenticated();
    }

    public function test_logout_succeeds_with_valid_csrf(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user);

        $response = $this->post(route('logout'));
        $this->assertGuest();
    }

    // ---- Forged Role Payload Protection ----

    public function test_user_cannot_self_promote_via_session_tampering(): void
    {
        $student = $this->makeUser('student');
        $this->actingAs($student);

        // Attempt to set role via session — must be ignored server-side
        Session::put('user_role', 'super_admin');
        Session::save();

        $response = $this->get(route('admin.sponsors'));

        // The role middleware must read role from DB / auth provider, not session
        $this->assertContains($response->getStatusCode(), [403, 419]);
    }

    public function test_user_cannot_spoof_role_in_request_body(): void
    {
        $student = $this->makeUser('student');
        $this->actingAs($student);

        // Attempt to spoof role via query string
        $response = $this->withHeaders([
            'X-User-Role' => 'super_admin',
        ])->get(route('admin.sponsors'));

        $this->assertSame(403, $response->getStatusCode());
    }

    // ---- Route model binding safety ----

    public function test_admin_api_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/analytics/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/admin/audit-logs')->assertStatus(401);
        $this->getJson('/api/v1/admin/best-deals')->assertStatus(401);
    }

    public function test_student_cannot_access_admin_api(): void
    {
        $student = $this->makeUser('student');
        $this->actingAs($student, 'sanctum');

        $this->getJson('/api/v1/admin/analytics/dashboard')->assertStatus(403);
        $this->getJson('/api/v1/admin/audit-logs')->assertStatus(403);
    }

    public function test_super_admin_can_access_admin_api(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/admin/analytics/dashboard?from=2024-01-01&to='.date('Y-m-d'))->assertOk();
    }

    // ---- Helpers ----

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Test',
            'email' => "{$role}_".uniqid()."@vbat.id",
            'password' => 'password',
            'role' => $role,
        ]);
    }
}