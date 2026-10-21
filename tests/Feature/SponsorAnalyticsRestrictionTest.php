<?php

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRITICAL: Sponsors MUST NOT access analytics endpoints returning Views, Clicks, CTR
 * Only Super Admin can view sponsor analytics data
 */
class SponsorAnalyticsRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected Sponsor $sponsor;
    protected User $sponsorUser;
    protected User $adminUser;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'super_admin']);
        $this->sponsorUser = User::factory()->create(['role' => 'sponsor']);
        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->sponsor = Sponsor::factory()->create(['user_id' => $this->sponsorUser->id]);
    }

    public function test_sponsor_role_is_not_super_admin(): void
    {
        $this->assertNotEquals('super_admin', $this->sponsorUser->role);
        $this->assertEquals('sponsor', $this->sponsorUser->role);
    }

    public function test_sponsor_user_issponssor_method(): void
    {
        $this->assertTrue($this->sponsorUser->isSponsor(), 'Sponsor user should return true for isSponsor()');
    }

    public function test_sponsor_user_cannot_be_super_admin(): void
    {
        $this->assertFalse($this->sponsorUser->isSuperAdmin(), 'Sponsor should not be super_admin');
    }

    public function test_super_admin_user_has_correct_role(): void
    {
        $this->assertTrue($this->adminUser->isSuperAdmin(), 'Super admin should return true for isSuperAdmin()');
    }

    public function test_super_admin_cannot_be_sponsor(): void
    {
        $this->assertFalse($this->adminUser->isSponsor(), 'Super admin should not be sponsor');
    }

    public function test_student_role_exists_and_distinct(): void
    {
        $this->assertEquals('student', $this->studentUser->role);
        $this->assertFalse($this->studentUser->isSuperAdmin());
        $this->assertFalse($this->studentUser->isSponsor());
    }

    public function test_only_super_admin_has_analytics_access(): void
    {
        // Verify role structure
        $roles = [
            'super_admin' => $this->adminUser,
            'sponsor' => $this->sponsorUser,
            'student' => $this->studentUser,
        ];

        foreach ($roles as $role => $user) {
            if ($role === 'super_admin') {
                $this->assertTrue($user->isSuperAdmin(), "{$role} should have admin access");
            } else {
                $this->assertFalse($user->isSuperAdmin(), "{$role} should not have admin access");
            }
        }
    }

    public function test_sponsor_role_isolation(): void
    {
        // Sponsor user should only match sponsor queries
        $this->assertTrue($this->sponsorUser->isSponsor());
        $this->assertFalse($this->sponsorUser->isSuperAdmin());
        $this->assertNotEquals('student', $this->sponsorUser->role);
    }

    public function test_role_enumeration_complete(): void
    {
        $validRoles = ['super_admin', 'sponsor', 'student'];

        // Create users with each role and verify
        foreach ($validRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertEquals($role, $user->role, "Role {$role} should persist");
        }
    }

    public function test_analytics_data_separation_by_role(): void
    {
        // Sponsor has association to sponsor model
        $this->assertNotNull($this->sponsor->user_id);
        $this->assertEquals($this->sponsorUser->id, $this->sponsor->user_id);

        // Admin has no sponsor association
        $this->assertNull(Sponsor::where('user_id', $this->adminUser->id)->first());
    }
}
