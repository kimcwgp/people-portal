<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RingCentralService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    private function minutesToHhMm(?int $minutes): string
    {
        if ($minutes === null) return '';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    }

    private function colorForAttendance(string $status): string
    {
        return strtoupper($status) === 'TIME IN' || strtoupper($status) === 'IN'
            ? '#0ea5e9' 
            : '#ef4444';
    }

    private function colorForBreak(string $type, string $action): string
    {
        $action = strtoupper($action);
        $map = [
            'lunch' => ['START' => '#f59e0b', 'END' => '#10b981'],
            'brb'   => ['START' => '#6366f1', 'END' => '#10b981'],
        ];
        return $map[$type][$action] ?? ($action === 'START' ? '#64748b' : '#10b981');
    }

    private function manila(Carbon $dt): string
    {
        return $dt->copy()->timezone('Asia/Manila')->format('M d, Y h:i A');
    }

    public function sendAttendanceNotification($user, string $status, Carbon $timestamp, string $glipUrl, ?string $notes = null): bool
    {
        $action = strtoupper($status) === 'TIME IN' ? 'logged in' : 'logged out';
        
        $message = sprintf(
            '**%s** %s at %s',
            $user->name,
            $action,
            $this->manila($timestamp)
        );

        $card = [
            'type' => 'Card',
            'title' => $message,
            'color' => $this->colorForAttendance($status),
            'thumb_url' => config('app.brand_logo_url'),
        ];

        if (!empty($notes)) {
            $card['fields'] = [
                [
                    'title' => 'Notes',
                    'value' => strlen($notes) > 200 ? substr($notes, 0, 200) . '...' : $notes,
                    'style' => 'Long'
                ]
            ];
        }

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral attendance notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendBreakNotification(
        $user,
        string $type,
        string $action,
        Carbon $timestamp,
        string $glipUrl,
        ?int $remainingMinutes = null,
        ?string $currentStatus = null,
        ?string $notes = null 
    ): bool {
        $action = strtoupper($action);
        
        $labels = [
            'lunch' => $action === 'START' ? 'started lunch break' : 'ended lunch break',
            'brb' => $action === 'START' ? 'went BRB' : 'back from BRB'
        ];
        
        $label = $labels[$type] ?? ($action === 'START' ? "started {$type} break" : "ended {$type} break");
        
        $remaining = '';
        if ($type === 'lunch' && $remainingMinutes) {
            $remaining = " ({$this->minutesToHhMm($remainingMinutes)} remaining)";
        }
        
        $message = sprintf(
            '**%s** %s at %s%s',
            $user->name,
            $label,
            $this->manila($timestamp),
            $remaining
        );

        $card = [
            'type' => 'Card',
            'title' => $message,
            'color' => $this->colorForBreak($type, $action),
            'thumb_url' => config('app.brand_logo_url'),
        ];

        if (!empty($notes)) {
            $card['fields'] = [
                [
                    'title' => 'Notes',
                    'value' => strlen($notes) > 200 ? substr($notes, 0, 200) . '...' : $notes,
                    'style' => 'Long'
                ]
            ];
        }

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral break notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendStandupNotification($user, array $standups, string $supervisorGlipUrl): bool
    {
        if (empty($standups)) {
            return false;
        }

        $standupDate = Carbon::parse($standups[0]->standup_date)->format('M d, Y');
        $submittedAt = $this->manila(Carbon::parse($standups[0]->created_at));
        $count = count($standups);

        $title = $count === 1
            ? sprintf('**%s** submitted a standup for %s (submitted at %s)', $user->name, $standupDate, $submittedAt)
            : sprintf('**%s** submitted %d standups for %s (submitted at %s)', $user->name, $count, $standupDate, $submittedAt);

        $fields = [];
        foreach ($standups as $index => $standup) {
            $projectName = $standup->project->project_name ?? 'General';
            $projectNumber = $index + 1;

            if ($count > 1) {
                $fields[] = [
                    'title' => "Project #{$projectNumber}: {$projectName}",
                    'value' => '',
                    'style' => 'Short'
                ];
            } else {
                $fields[] = [
                    'title' => 'Project',
                    'value' => $projectName,
                    'style' => 'Short'
                ];
            }

            if (!empty($standup->time_spent_minutes)) {
                $timeFormatted = $this->minutesToHhMm($standup->time_spent_minutes);
                $fields[] = [
                    'title' => 'Time Spent',
                    'value' => $timeFormatted,
                    'style' => 'Short'
                ];
            }

            if (!empty($standup->notes)) {
                $notes = strlen($standup->notes) > 200 
                    ? substr($standup->notes, 0, 200) . '...' 
                    : $standup->notes;
                
                $fields[] = [
                    'title' => 'What they did',
                    'value' => $notes,
                    'style' => 'Long'
                ];
            }

            if (!empty($standup->impediments)) {
                $impediments = strlen($standup->impediments) > 150 
                    ? substr($standup->impediments, 0, 150) . '...' 
                    : $standup->impediments;
                
                $fields[] = [
                    'title' => 'Impediments/Blockers',
                    'value' => $impediments,
                    'style' => 'Long'
                ];
            }

            if ($count > 1 && $index < $count - 1) {
                $fields[] = [
                    'title' => '─────────────────',
                    'value' => '',
                    'style' => 'Long'
                ];
            }
        }

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#10b981',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields,
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($supervisorGlipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral standup notify failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'supervisor_glip_url' => $supervisorGlipUrl,
                'standups_count' => count($standups)
            ]);
            return false;
        }
    }

    public function sendLoginNotification($email, $loginLink, $glipUrl)
    {
        try {
            $message = [
                'text' => "Please login using this [link]({$loginLink})",
                'color' => '#3b82f6',
                'attachments' => [
                    [
                        'type' => 'Card',
                        'fallback' => 'Login link for ' . $email,
                        'color' => '#667eea',
                        'thumb_url' => config('app.brand_logo_url'),
                        'fields' => [
                            [
                                'title' => 'Login Link',
                                'value' => "[Click here to login]({$loginLink})",
                                'style' => 'Long'
                            ],
                            [
                                'title' => 'Expires',
                                'value' => '15 minutes',
                                'style' => 'Short'
                            ]
                        ],
                        'actions' => [
                            [
                                'type' => 'Action.OpenUrl',
                                'title' => 'Login Now',
                                'url' => $loginLink
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->client->post($glipUrl, [
                'json' => $message,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            Log::error('RingCentral notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendAutoTimeOutNotification($user, $glipUrl)
    {
        try {
            $message = sprintf(
                'Hi **%s**, i noticed that you haven\'t time out yet. I took the liberty of time you out already.',
                $user->name
            );

            $card = [
                'type' => 'Card',
                'title' => $message,
                'color' => '#ef4444',
                'thumb_url' => config('app.brand_logo_url'),
            ];

            $payload = ['attachments' => [$card]];

            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);

            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral logout notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendLeaveApprovalNotification($leave, string $action, $approver, string $glipUrl, ?string $rejectionNote = null): bool
    {
        $actionText = $action === 'approved' ? 'approved' : 'rejected';
        $color = $action === 'approved' ? '#10b981' : '#ef4444';
        
        $startDate = $leave->start_date ? $leave->start_date->format('M d, Y') : 'N/A';
        $endDate = $leave->end_date ? $leave->end_date->format('M d, Y') : 'N/A';
        $duration = $startDate === $endDate ? $startDate : "{$startDate} - {$endDate}";
        
        $title = sprintf(
            'Your %s leave request has been %s by %s',
            $leave->leaveType->name ?? 'Leave',
            $actionText,
            $approver->name
        );

        $fields = [
            [
                'title' => 'Duration',
                'value' => $duration,
                'style' => 'Short'
            ],
            [
                'title' => 'Leave Type',
                'value' => $leave->leaveType->name ?? 'Leave',
                'style' => 'Short'
            ]
        ];

        if ($leave->reason) {
            $fields[] = [
                'title' => 'Reason',
                'value' => $leave->reason,
                'style' => 'Long'
            ];
        }

        if ($rejectionNote) {
            $fields[] = [
                'title' => 'Rejection Reason',
                'value' => $rejectionNote,
                'style' => 'Long'
            ];
        }

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => $color,
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral leave approval notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendOvertimeApprovalNotification($overtime, string $action, $approver, string $glipUrl, ?string $rejectionNote = null): bool
    {
        $actionText = $action === 'APPROVED' ? 'approved' : 'rejected';
        $color = $action === 'APPROVED' ? '#10b981' : '#ef4444';
        
        $otDate = $overtime->ot_date ? $overtime->ot_date->format('M d, Y') : 'N/A';
        $timeRange = ($overtime->time_in ? $overtime->time_in->format('H:i') : 'N/A') . ' - ' . 
                    ($overtime->time_out ? $overtime->time_out->format('H:i') : 'N/A');
        
        $title = sprintf(
            'Your overtime request has been %s by %s',
            $actionText,
            $approver->name
        );

        $fields = [
            [
                'title' => 'Date',
                'value' => $otDate,
                'style' => 'Short'
            ],
            [
                'title' => 'Hours',
                'value' => $overtime->formatted_ot_hours ?? ($overtime->ot_hours . 'h'),
                'style' => 'Short'
            ],
            [
                'title' => 'Time',
                'value' => $timeRange,
                'style' => 'Short'
            ],
            [
                'title' => 'Project',
                'value' => $overtime->project->project_name ?? 'No Project',
                'style' => 'Short'
            ]
        ];

        if ($overtime->notes) {
            $fields[] = [
                'title' => 'Your Notes',
                'value' => $overtime->notes,
                'style' => 'Long'
            ];
        }

        if ($rejectionNote) {
            $fields[] = [
                'title' => 'Rejection Reason',
                'value' => $rejectionNote,
                'style' => 'Long'
            ];
        }

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => $color,
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral overtime approval notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendLeaveCancellationNotification($leave, $user, string $supervisorGlipUrl, string $cancellationReason): bool
    {
        $startDate = $leave->start_date ? $leave->start_date->format('M d, Y') : 'N/A';
        $endDate = $leave->end_date ? $leave->end_date->format('M d, Y') : 'N/A';
        $duration = $startDate === $endDate ? $startDate : "{$startDate} - {$endDate}";
        
        $title = sprintf(
            '**%s** cancelled their %s leave request',
            $user->name,
            $leave->leaveType->name ?? 'Leave'
        );

        $fields = [
            [
                'title' => 'Duration',
                'value' => $duration,
                'style' => 'Short'
            ],
            [
                'title' => 'Leave Type',
                'value' => $leave->leaveType->name ?? 'Leave',
                'style' => 'Short'
            ],
            [
                'title' => 'Original Reason',
                'value' => $leave->reason ?? 'No reason provided',
                'style' => 'Long'
            ],
            [
                'title' => 'Cancellation Reason',
                'value' => $cancellationReason,
                'style' => 'Long'
            ],
            [
                'title' => 'Cancelled At',
                'value' => $this->manila(now()),
                'style' => 'Short'
            ]
        ];

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#f59e0b',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($supervisorGlipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral leave cancellation notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendOvertimeCancellationNotification($overtime, $user, string $supervisorGlipUrl, string $cancellationReason): bool
    {
        $otDate = $overtime->ot_date ? Carbon::parse($overtime->ot_date)->format('M d, Y') : 'N/A';
        $hours = $this->minutesToHhMm($overtime->ot_hours);
        $timeRange = ($overtime->time_in ?? 'N/A') . ' - ' . ($overtime->time_out ?? 'N/A');
        
        $title = sprintf(
            '**%s** cancelled their overtime request',
            $user->name
        );

        $fields = [
            [
                'title' => 'OT Date',
                'value' => $otDate,
                'style' => 'Short'
            ],
            [
                'title' => 'Time Period',
                'value' => $timeRange,
                'style' => 'Short'
            ],
            [
                'title' => 'OT Hours',
                'value' => $hours,
                'style' => 'Short'
            ],
            [
                'title' => 'Project',
                'value' => $overtime->project->name ?? 'N/A',
                'style' => 'Short'
            ],
            [
                'title' => 'Original Reason',
                'value' => $overtime->reason ?? 'No reason provided',
                'style' => 'Long'
            ],
            [
                'title' => 'Cancellation Reason',
                'value' => $cancellationReason,
                'style' => 'Long'
            ],
            [
                'title' => 'Cancelled At',
                'value' => $this->manila(now()),
                'style' => 'Short'
            ]
        ];

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#f59e0b',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($supervisorGlipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral overtime cancellation notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendAttendanceCorrectionRequestNotification($correction, $user, string $supervisorGlipUrl): bool
    {
        $attendanceDate = $correction->attendance->attendance_date 
            ? Carbon::parse($correction->attendance->attendance_date)->format('M d, Y') 
            : 'N/A';
        
        $currentTimeIn = $correction->attendance->time_in 
            ? Carbon::parse($correction->attendance->time_in)->format('h:i A') 
            : 'N/A';
        
        $currentTimeOut = $correction->attendance->time_out 
            ? Carbon::parse($correction->attendance->time_out)->format('h:i A') 
            : 'N/A';
        
        $correctedTimeIn = $correction->corrected_time_in 
            ? Carbon::parse($correction->corrected_time_in)->format('h:i A') 
            : 'N/A';
        
        $correctedTimeOut = $correction->corrected_time_out 
            ? Carbon::parse($correction->corrected_time_out)->format('h:i A') 
            : 'N/A';
        
        // Get lunch break times from attendance breaks
        $currentLunchStart = 'N/A';
        $currentLunchEnd = 'N/A';
        if ($correction->attendance && $correction->attendance->breaks) {
            $lunchBreaks = $correction->attendance->breaks->where('type', 'lunch');
            if ($lunchBreaks->isNotEmpty()) {
                // Get the first lunch break's start time and last lunch break's end time
                $firstLunch = $lunchBreaks->sortBy('started_at')->first();
                $lastLunch = $lunchBreaks->sortByDesc('ended_at')->first();
                
                $currentLunchStart = $firstLunch->started_at 
                    ? Carbon::parse($firstLunch->started_at)->format('h:i A') 
                    : 'N/A';
                $currentLunchEnd = $lastLunch->ended_at 
                    ? Carbon::parse($lastLunch->ended_at)->format('h:i A') 
                    : 'N/A';
            }
        }
        
        $correctedLunchStart = $correction->corrected_lunch_start 
            ? Carbon::parse($correction->corrected_lunch_start)->format('h:i A') 
            : 'N/A';
        
        $correctedLunchEnd = $correction->corrected_lunch_end 
            ? Carbon::parse($correction->corrected_lunch_end)->format('h:i A') 
            : 'N/A';
        
        $submittedAt = $this->manila(Carbon::parse($correction->created_at));
        
        $title = sprintf(
            '⏰ **%s** submitted an attendance correction request',
            $user->name
        );

        $fields = [
            [
                'title' => 'Attendance Date',
                'value' => $attendanceDate . ' | **Submitted:** ' . $submittedAt,
                'style' => 'Long'
            ],
            [
                'title' => 'Current Times',
                'value' => '**In:** ' . $currentTimeIn . ' | **Out:** ' . $currentTimeOut,
                'style' => 'Long'
            ],
            [
                'title' => 'Corrected Times',
                'value' => '**In:** ' . $correctedTimeIn . ' | **Out:** ' . $correctedTimeOut,
                'style' => 'Long'
            ],
            [
                'title' => 'Current Lunch Break',
                'value' => '**Start:** ' . $currentLunchStart . ' | **End:** ' . $currentLunchEnd,
                'style' => 'Long'
            ],
            [
                'title' => 'Corrected Lunch Break',
                'value' => '**Start:** ' . $correctedLunchStart . ' | **End:** ' . $correctedLunchEnd,
                'style' => 'Long'
            ]
        ];

        if ($correction->reason) {
            $fields[] = [
                'title' => 'Reason',
                'value' => $correction->reason,
                'style' => 'Long'
            ];
        }

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#f59e0b',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($supervisorGlipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral attendance correction request notify failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendAttendanceCorrectionApprovalNotification($correction, string $action, $approver, string $glipUrl, ?string $rejectionNote = null): bool
    {
        $actionText = $action === 'approved' ? 'approved' : 'rejected';
        $icon = $action === 'approved' ? '✅' : '❌';
        $color = $action === 'approved' ? '#10b981' : '#ef4444';
        
        $attendanceDate = $correction->attendance->attendance_date 
            ? Carbon::parse($correction->attendance->attendance_date)->format('M d, Y') 
            : 'N/A';
        
        $correctedTimeIn = $correction->corrected_time_in 
            ? Carbon::parse($correction->corrected_time_in)->format('h:i A') 
            : 'N/A';
        
        $correctedTimeOut = $correction->corrected_time_out 
            ? Carbon::parse($correction->corrected_time_out)->format('h:i A') 
            : 'N/A';
        
        $correctedLunchStart = $correction->corrected_lunch_start 
            ? Carbon::parse($correction->corrected_lunch_start)->format('h:i A') 
            : 'N/A';
        
        $correctedLunchEnd = $correction->corrected_lunch_end 
            ? Carbon::parse($correction->corrected_lunch_end)->format('h:i A') 
            : 'N/A';
        
        $processedAt = $this->manila(now());
        
        $title = sprintf(
            '%s Your attendance correction request has been %s by %s',
            $icon,
            $actionText,
            $approver->name
        );

        $fields = [
            [
                'title' => 'Attendance Date',
                'value' => $attendanceDate . ' | **Processed:** ' . $processedAt,
                'style' => 'Long'
            ],
            [
                'title' => 'Corrected Times',
                'value' => '**In:** ' . $correctedTimeIn . ' | **Out:** ' . $correctedTimeOut,
                'style' => 'Long'
            ],
            [
                'title' => 'Corrected Lunch Break',
                'value' => '**Start:** ' . $correctedLunchStart . ' | **End:** ' . $correctedLunchEnd,
                'style' => 'Long'
            ]
        ];

        if ($correction->reason) {
            $fields[] = [
                'title' => 'Your Reason',
                'value' => $correction->reason,
                'style' => 'Long'
            ];
        }

        if ($rejectionNote) {
            $fields[] = [
                'title' => 'Rejection Reason',
                'value' => $rejectionNote,
                'style' => 'Long'
            ];
        }

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => $color,
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral attendance correction approval notify failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Send attendance notification to team webhook
     */
    public function sendToTeamAttendanceWebhook($user, string $status, Carbon $timestamp, ?string $notes = null): bool
    {
        $webhookUrl = config('services.ringcentral.team_attendance_webhook');
        
        if (empty($webhookUrl)) {
            return false;
        }

        return $this->sendAttendanceNotification($user, $status, $timestamp, $webhookUrl, $notes);
    }

    /**
     * Send break notification to team webhook
     */
    public function sendToTeamBreakWebhook(
        $user,
        string $type,
        string $action,
        Carbon $timestamp,
        ?int $remainingMinutes = null,
        ?string $notes = null
    ): bool {
        $webhookUrl = config('services.ringcentral.team_attendance_webhook');
        
        if (empty($webhookUrl)) {
            return false;
        }

        return $this->sendBreakNotification(
            $user,
            $type,
            $action,
            $timestamp,
            $webhookUrl,
            $remainingMinutes,
            null,
            $notes
        );
    }

    /**
     * Send auto-approval notification for leave to employee
     */
    public function sendLeaveAutoApprovalNotification($leave, $user, string $glipUrl): bool
    {
        $leaveType = $leave->leaveType->name ?? 'Leave';
        $startDate = Carbon::parse($leave->start_date)->format('M d, Y');
        $endDate = Carbon::parse($leave->end_date)->format('M d, Y');
        $duration = $leave->duration ?? 'N/A';

        $title = "✅ Leave Auto-Approved - Your {$leaveType} request has been automatically approved";

        $fields = [
            ['title' => 'Employee', 'value' => $user->name, 'style' => 'Short'],
            ['title' => 'Leave Type', 'value' => $leaveType, 'style' => 'Short'],
            ['title' => 'Duration', 'value' => $duration, 'style' => 'Short'],
            ['title' => 'Date Range', 'value' => "{$startDate} - {$endDate}", 'style' => 'Short'],
            ['title' => 'Reason', 'value' => $leave->reason ?? 'N/A', 'style' => 'Long'],
            ['title' => 'Approved By', 'value' => 'System (Auto-Approval)', 'style' => 'Short'],
            ['title' => 'Approved At', 'value' => $this->manila(Carbon::now()), 'style' => 'Short'],
        ];

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#10b981',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral leave auto-approval notify failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Send auto-approval notification for leave to supervisor
     */
    public function sendLeaveAutoApprovalToSupervisor($leave, $user, string $glipUrl): bool
    {
        $leaveType = $leave->leaveType->name ?? 'Leave';
        $startDate = Carbon::parse($leave->start_date)->format('M d, Y');
        $endDate = Carbon::parse($leave->end_date)->format('M d, Y');

        $title = "🔔 Leave Auto-Approved - {$user->name}'s {$leaveType} request was automatically approved";

        $fields = [
            ['title' => 'Employee', 'value' => $user->name, 'style' => 'Short'],
            ['title' => 'Leave Type', 'value' => $leaveType, 'style' => 'Short'],
            ['title' => 'Date Range', 'value' => "{$startDate} - {$endDate}", 'style' => 'Short'],
            ['title' => 'Status', 'value' => 'Auto-Approved by System', 'style' => 'Short'],
        ];

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#f59e0b',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral leave auto-approval supervisor notify failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Send auto-approval notification for overtime to employee
     */
    public function sendOvertimeAutoApprovalNotification($overtime, $user, string $glipUrl): bool
    {
        $otDate = Carbon::parse($overtime->ot_date)->format('M d, Y');
        $hours = $this->minutesToHhMm($overtime->ot_hours);

        $title = "✅ Overtime Auto-Approved - Your overtime request has been automatically approved";

        $fields = [
            ['title' => 'Employee', 'value' => $user->name, 'style' => 'Short'],
            ['title' => 'OT Date', 'value' => $otDate, 'style' => 'Short'],
            ['title' => 'OT Hours', 'value' => $hours, 'style' => 'Short'],
            ['title' => 'Reason', 'value' => $overtime->reason ?? 'N/A', 'style' => 'Long'],
            ['title' => 'Approved By', 'value' => 'System (Auto-Approval)', 'style' => 'Short'],
            ['title' => 'Approved At', 'value' => $this->manila(Carbon::now()), 'style' => 'Short'],
        ];

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#10b981',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral overtime auto-approval notify failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Send auto-approval notification for overtime to supervisor
     */
    public function sendOvertimeAutoApprovalToSupervisor($overtime, $user, string $glipUrl): bool
    {
        $otDate = Carbon::parse($overtime->ot_date)->format('M d, Y');
        $hours = $this->minutesToHhMm($overtime->ot_hours);

        $title = "🔔 Overtime Auto-Approved - {$user->name}'s overtime request was automatically approved";

        $fields = [
            ['title' => 'Employee', 'value' => $user->name, 'style' => 'Short'],
            ['title' => 'OT Date', 'value' => $otDate, 'style' => 'Short'],
            ['title' => 'OT Hours', 'value' => $hours, 'style' => 'Short'],
            ['title' => 'Status', 'value' => 'Auto-Approved by System', 'style' => 'Short'],
        ];

        $card = [
            'type' => 'Card',
            'title' => $title,
            'color' => '#f59e0b',
            'thumb_url' => config('app.brand_logo_url'),
            'fields' => $fields
        ];

        $payload = ['attachments' => [$card]];

        try {
            $res = $this->client->post($glipUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 5,
            ]);
            return $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        } catch (\Throwable $e) {
            Log::warning('RingCentral overtime auto-approval supervisor notify failed: '.$e->getMessage());
            return false;
        }
    }
}

