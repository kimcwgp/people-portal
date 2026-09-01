<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasManyThrough, HasOne};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use App\Models\Team;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\PersonalInformation;
use App\Models\JobInformation;
use App\Models\SalaryInformation;
use App\Models\EmploymentHistory;
use App\Models\Attendance;
use App\Models\ForApproval;
use App\Models\Leave;
use App\Models\LeaveCredit;
use App\Models\Overtime;
use App\Models\Project;
use App\Models\Standup;

use App\Traits\Sortable;
use App\Traits\Filterable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles;
    use Sortable, Filterable;

    protected $guard_name = 'sanctum';

    public function guardName(): string
    {
        return 'sanctum';
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'glip_url',
        'status',
        'online',
        'team_id',
        'shift_id',
        'immediate_sup_id', 
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'online' => 'boolean',
    ];

    protected $sortable = [
        'id',
        'name',
        'email',
        'status',
        'online',
        'team_id',
        'created_at',
        'updated_at',
    ];

    public function scopeFilterByTeam($query, $teamId = null)
    {
        if (!$teamId) return $query;
        
        return $query->where('team_id', $teamId);
    }

    public function scopeFilterBySupervisor($query, $supervisorId = null)
    {
        if (!$supervisorId) return $query;

        return $query->where('immediate_sup_id', $supervisorId);
    }

    public function scopeFilterByShift($query, $shiftId = null)
    {
        if (!$shiftId) return $query;

        return $query->where('shift_id', $shiftId);
    }

    public function scopeFilterByActiveStatus($query, $status = null)
    {
        if (is_null($status)) return $query;

        return $query->where('status', (bool) $status);
    }

    public function scopeFilterByOnlineStatus($query, $online = null)
    {
        if (is_null($online)) return $query;

        return $query->where('online', (bool) $online);
    }

    public function scopeSearch($query, $search = null, $columns = ['name'])
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }

    public function scopeFilterByRole($query, $roleId = null)
    {
        if (!$roleId) return $query;

        return $query->whereHas('roles', function ($q) use ($roleId) {
            $q->where('roles.id', $roleId);
        });
    }

    public function scopeWithoutTeam($query)
    {
        return $query->whereNull('team_id');
    }

    public function scopeWithoutSupervisor($query)
    {
        return $query->whereNull('immediate_sup_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function immediateSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'immediate_sup_id');
    }

    public function directSubordinates(): HasMany
    {
        return $this->hasMany(User::class, 'immediate_sup_id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function personalInformation(): HasOne
    {
        return $this->hasOne(PersonalInformation::class);
    }

    public function jobInformation(): HasMany
    {
        return $this->hasMany(JobInformation::class);
    }

    public function currentJobInformation(): HasOne
    {
        return $this->hasOne(JobInformation::class)->latest();
    }

    public function salaryInformation(): HasMany
    {
        return $this->hasMany(SalaryInformation::class);
    }

    public function currentSalaryInformation(): HasOne
    {
        return $this->hasOne(SalaryInformation::class)->latest();
    }

    public function employmentHistory(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function isActive(): bool
    {
        return $this->todayAttendance()
                    ->whereNull('time_out')
                    ->exists();
    }

    public function todayAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)
                    ->whereDate('attendance_date', today())
                    ->whereNotNull('time_in')
                    ->latest();
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ForApproval::class, 'user_id');
    }

    public function approvalsPending(): HasMany
    {
        return $this->hasMany(ForApproval::class, 'approver_id');
    }

    public function managedUsers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            JobInformation::class,
            'manager_id',
            'id',
            'id',
            'user_id'
        )->whereNull('job_information.end_date');
    }

    public function currentManager()
    {
        $jobInfo = $this->currentJobInformation;
        return $jobInfo ? $jobInfo->manager : null;
    }

    public function headsTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'team_head_id');
    }

    public function approvedSalaries(): HasMany
    {
        return $this->hasMany(SalaryInformation::class, 'approved_by');
    }

    public function terminatedEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'terminated_by');
    }

    public function createdEmploymentHistory(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class, 'created_by');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'user_id');
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function currentYearLeaveCredits(): HasOne
    {
        return $this->hasOne(LeaveCredit::class)
            ->where('year', now()->year);
    }

    public function leavesToApprove(): HasMany
    {
        return $this->hasMany(Leave::class, 'approver_id');
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class, 'user_id');
    }

    public function overtimesToApprove(): HasMany
    {
        return $this->hasMany(Overtime::class, 'approver_id');
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'pm_id');
    }

    public function standups(): HasMany
    {
        return $this->hasMany(Standup::class);
    }

    public function isSupervisorOf(User $user): bool
    {
        return $user->immediate_sup_id === $this->id;
    }

    public function hasSupervisor(): bool
    {
        return !is_null($this->immediate_sup_id);
    }

    public function getAllSubordinates()
    {
        return User::where('immediate_sup_id', $this->id)
            ->with('directSubordinates')
            ->get()
            ->flatMap(function ($subordinate) {
                return collect([$subordinate])->merge($subordinate->getAllSubordinates());
            });
    }

    public function getSupervisorChain()
    {
        $chain = collect();
        $current = $this->immediateSupervisor;
        
        while ($current) {
            $chain->push($current);
            $current = $current->immediateSupervisor;
        }
        
        return $chain;
    }

    public function associateLogs(): HasMany
    {
        return $this->hasMany(AssociateLog::class);
    }
}
