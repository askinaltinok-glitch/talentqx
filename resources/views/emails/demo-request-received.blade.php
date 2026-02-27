<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 20px 0 10px;">
        <img src="https://talentqx.com/assets/logo-email.png" alt="TalentQX" style="max-height: 48px; margin-bottom: 12px;">
    </div>
    <h2 style="color: #1e40af;">New Demo Request</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr><td style="padding: 8px 0; font-weight: bold;">Name:</td><td>{{ $r->full_name }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Company:</td><td>{{ $r->company }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Email:</td><td><a href="mailto:{{ $r->email }}">{{ $r->email }}</a></td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Country:</td><td>{{ $r->country ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Company Type:</td><td>{{ $r->company_type ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Fleet Size:</td><td>{{ $r->fleet_size ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Active Crew:</td><td>{{ $r->active_crew ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Monthly Hires:</td><td>{{ $r->monthly_hires ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Vessel Types:</td><td>{{ $r->vessel_types ? implode(', ', $r->vessel_types) : '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Main Ranks:</td><td>{{ $r->main_ranks ? implode(', ', $r->main_ranks) : '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Locale:</td><td>{{ $r->locale ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">Source:</td><td>{{ $r->source ?? '—' }}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: bold;">IP:</td><td>{{ $r->ip ?? '—' }}</td></tr>
    </table>
    @if($r->message)
    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">
    <p style="font-weight: bold;">Message:</p>
    <p>{{ $r->message }}</p>
    @endif
    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">
    <p style="font-size: 12px; color: #9ca3af;">Submitted {{ $r->created_at->format('Y-m-d H:i') }} UTC</p>
</body>
</html>
