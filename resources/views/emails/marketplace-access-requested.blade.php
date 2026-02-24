<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #1e40af;">New Candidate Access Request</h2>

    <p>A company has requested access to one of the candidates in the marketplace.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 8px 0; font-weight: bold; width: 160px;">Requesting Company:</td>
            <td>{{ $req->requestingCompany?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Requested By:</td>
            <td>{{ trim(($req->requestingUser?->first_name ?? '') . ' ' . ($req->requestingUser?->last_name ?? '')) ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Candidate:</td>
            <td>{{ trim(($req->candidate?->first_name ?? '') . ' ' . ($req->candidate?->last_name ?? '')) ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Request Date:</td>
            <td>{{ $req->created_at->format('d M Y H:i') }} UTC</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Expires:</td>
            <td style="color: #dc2626;">{{ $expiresAt }}</td>
        </tr>
    </table>

    @if($req->request_message)
    <div style="background: #f9fafb; border-left: 3px solid #1e40af; padding: 12px 16px; margin: 16px 0;">
        <p style="margin: 0; font-weight: bold; font-size: 13px; color: #6b7280;">Message:</p>
        <p style="margin: 8px 0 0;">{{ $req->request_message }}</p>
    </div>
    @endif

    <p style="margin-top: 24px;">Please review this request in the admin panel, or use the links below:</p>

    <div style="margin: 24px 0; text-align: center;">
        <a href="{{ $reviewUrl }}" style="display: inline-block; background: #1e40af; color: #fff; padding: 12px 32px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-right: 8px;">
            Review Request
        </a>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
    <p style="font-size: 12px; color: #9ca3af;">
        This request will automatically expire on {{ $expiresAt }} if not responded to.
        <br>Octopus-AI Marketplace
    </p>
</body>
</html>
