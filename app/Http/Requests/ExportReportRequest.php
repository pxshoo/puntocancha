<?php

namespace App\Http\Requests;

class ExportReportRequest extends DashboardRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin' && $this->user()?->club_id !== null;
    }
}
