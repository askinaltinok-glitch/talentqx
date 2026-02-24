<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;">
    @if($outcome === 'approved')
    <h2 style="color: #059669;">Candidate Access Request — Approved</h2>
    <p>Your access request has been <strong style="color: #059669;">approved</strong>.</p>
    @else
    <h2 style="color: #dc2626;">Candidate Access Request — Declined</h2>
    <p>Your access request has been <strong style="color: #dc2626;">declined</strong>.</p>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 8px 0; font-weight: bold; width: 140px;">Candidate:</td>
            <td>{{ $candidateName }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Request Date:</td>
            <td>{{ $req->created_at->format('d M Y H:i') }} UTC</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Response Date:</td>
            <td>{{ $req->responded_at?->format('d M Y H:i') ?? '—' }} UTC</td>
        </tr>
    </table>

    @if($req->response_message)
    <div style="background: #f9fafb; border-left: 3px solid {{ $outcome === 'approved' ? '#059669' : '#dc2626' }}; padding: 12px 16px; margin: 16px 0;">
        <p style="margin: 0; font-weight: bold; font-size: 13px; color: #6b7280;">Response message:</p>
        <p style="margin: 8px 0 0;">{{ $req->response_message }}</p>
    </div>
    @endif

    @if($outcome === 'approved')
    <p>You can now view the candidate's full profile in the marketplace.</p>
    @else
    <p>You may submit a new request in the future if needed.</p>
    @endif

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
    <p style="font-size: 12px; color: #9ca3af;">Octopus-AI Marketplace</p>
</body>
</html>
