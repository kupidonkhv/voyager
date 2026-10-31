<?php

namespace TCG\Voyager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Models\ActivityLog;
use TCG\Voyager\Models\Gallery;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\Setting;

class ActivityLogTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::loginUsingId(1);
        Storage::fake(config('voyager.storage.disk', 'public'));
    }

    public function testActivityLogTableIsInstalledWithPermissions()
    {
        $this->assertTrue(Schema::hasTable('voyager_activity_logs'));
        $this->seeInDatabase('permissions', ['key' => 'browse_activity_logs']);
        $this->seeInDatabase('permissions', ['key' => 'browse_activity_log_context']);
    }

    public function testRecordUpdateStoresFieldNamesWithoutValues()
    {
        $response = $this->call('PUT', route('voyager.users.update', 1), [
            'name'  => 'Changed Admin',
            'email' => 'changed-admin@example.com',
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302]);

        $log = ActivityLog::where('event', 'user.updated')->latest('id')->firstOrFail();

        $this->assertSame('user', $log->actor_type);
        $this->assertSame('1', $log->actor_id);
        $this->assertSame('user', $log->subject_type);
        $this->assertSame('1', $log->subject_id);
        $this->assertContains('name', $log->changed_fields);
        $this->assertContains('email', $log->changed_fields);

        $serialized = json_encode($log->toArray());
        $this->assertStringNotContainsString('Changed Admin', $serialized);
        $this->assertStringNotContainsString('changed-admin@example.com', $serialized);
        $this->assertNull($log->metadata);
    }

    public function testRecordDeletionStoresOnlyIdentifierAndType()
    {
        $role = Role::create([
            'name'         => 'activity-test-role',
            'display_name' => 'Activity test role',
        ]);

        $response = $this->call('DELETE', route('voyager.roles.destroy', $role));

        $this->assertContains($response->getStatusCode(), [200, 302]);

        $log = ActivityLog::where('event', 'role.deleted')
            ->where('subject_id', (string) $role->id)
            ->firstOrFail();

        $this->assertSame('role', $log->subject_type);
        $this->assertSame('roles', $log->resource);
        $this->assertEmpty($log->changed_fields);
        $this->assertEmpty($log->metadata);

        $serialized = json_encode($log->toArray());
        $this->assertStringNotContainsString('activity-test-role', $serialized);
        $this->assertStringNotContainsString('Activity test role', $serialized);
    }

    public function testFileUploadStoresPathWithoutFileContents()
    {
        $gallery = Gallery::create([
            'title'      => 'Activity gallery',
            'dimensions' => '400x300',
        ]);

        $response = $this->call('POST', route('voyager.galleries.upload-media', $gallery), [], [], [
            'file' => UploadedFile::fake()->image('activity.jpg', 640, 480)->size(200),
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $log = ActivityLog::where('event', 'media.uploaded')->latest('id')->firstOrFail();

        $this->assertSame('media', $log->subject_type);
        $this->assertNotEmpty($log->subject_id);
        $this->assertSame('galleries', $log->resource);
        $this->assertArrayHasKey('path', $log->metadata);
        $this->assertArrayHasKey('disk', $log->metadata);
        $this->assertArrayHasKey('size', $log->metadata);
        $this->assertStringNotContainsString('<?php', (string) json_encode($log->metadata));
    }

    public function testSettingUpdateStoresKeyAndFieldNameWithoutValue()
    {
        Setting::query()->delete();

        $setting = Setting::create([
            'key'          => 'activity.secret',
            'display_name' => 'Activity secret',
            'value'        => 'old-secret',
            'type'         => 'text',
            'order'        => 0,
            'group'        => 'activity',
        ]);

        $response = $this->call('PUT', route('voyager.settings.update'), [
            'activity_secret'       => 'new-secret-value',
            'activity_secret_group' => 'activity',
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302]);

        $log = ActivityLog::where('event', 'setting.updated')
            ->where('subject_id', (string) $setting->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('setting', $log->subject_type);
        $this->assertSame('settings', $log->resource);
        $this->assertContains('value', $log->changed_fields);
        $this->assertSame('activity.secret', $log->metadata['key']);

        $serialized = json_encode($log->toArray());
        $this->assertStringNotContainsString('old-secret', $serialized);
        $this->assertStringNotContainsString('new-secret-value', $serialized);
    }

    public function testRolePermissionAssignmentStoresPermissionIdentifier()
    {
        $role = Role::create([
            'name'         => 'activity-permission-role',
            'display_name' => 'Activity permission role',
        ]);
        $permission = Permission::where('key', 'browse_activity_logs')->firstOrFail();

        $response = $this->call('PUT', route('voyager.roles.update', $role), [
            'name'         => 'activity-permission-role',
            'display_name' => 'Activity permission role',
            'permissions'  => [$permission->id],
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302]);

        $log = ActivityLog::where('event', 'role.permission_attached')
            ->where('subject_id', (string) $role->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('role', $log->subject_type);
        $this->assertSame((string) $permission->id, (string) $log->metadata['permission_id']);
    }

    public function testActivityLogPageIsAvailableToAuthorizedAdmin()
    {
        $this->call('GET', route('voyager.activity.index'))->assertStatus(200);
        $this->call('GET', route('voyager.activity.index'))->assertSee(__('voyager::activity.title'));
    }

    public function testActivityRecordingCanBeDisabled()
    {
        config(['voyager-activity.enabled' => false]);

        app(\TCG\Voyager\Activity\ActivityRecorder::class)->recordEventSafe(
            'record.created',
            'success',
            null,
            'users',
            [],
            [],
            'user',
            '1'
        );

        $this->assertSame(0, ActivityLog::count());
    }
}
