<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\InterviewInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InterviewInviteController extends Controller
{
    /**
     * GET /api/v1/maritime/interview/invite/{invitationId}
     *
     * Signed URL endpoint. Validates the Laravel signature, checks invitation
     * status, then redirects to the frontend interview page with the token.
     *
     * Flow: Email → signed backend URL → validate → redirect to frontend
     */
    public function redirect(Request $request, string $invitationId)
    {
        $invitation = InterviewInvitation::find($invitationId);

        if (!$invitation) {
            Log::warning('InterviewInviteController: invitation not found', [
                'invitation_id' => $invitationId,
            ]);
            return $this->errorRedirect('invalid', $request->query('locale', 'en'));
        }

        // Real-time expiry check
        if ($invitation->isExpired()) {
            if ($invitation->status !== InterviewInvitation::STATUS_EXPIRED) {
                $invitation->markExpired();
            }
            return $this->errorRedirect('expired', $invitation->locale ?? 'en');
        }

        // Already completed
        if ($invitation->status === InterviewInvitation::STATUS_COMPLETED) {
            return $this->errorRedirect('completed', $invitation->locale ?? 'en');
        }

        $locale = $invitation->locale ?? 'en';
        $token = $invitation->invitation_token;
        $frontendDomain = config('app.frontend_url', 'https://octopus-ai.net');

        Log::info('InterviewInviteController: redirecting to frontend', [
            'invitation_id' => $invitation->id,
            'candidate_id' => $invitation->pool_candidate_id,
            'locale' => $locale,
        ]);

        return redirect("{$frontendDomain}/{$locale}/maritime/interview?token={$token}");
    }

    /**
     * Build error redirect URL to frontend error page.
     */
    private function errorRedirect(string $reason, string $locale): \Illuminate\Http\RedirectResponse
    {
        $frontendDomain = config('app.frontend_url', 'https://octopus-ai.net');

        return redirect("{$frontendDomain}/{$locale}/maritime/interview?error={$reason}");
    }
}
