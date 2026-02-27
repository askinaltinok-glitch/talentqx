@php
$_logoUrl = '';
$_altText = 'TalentQX';

if (isset($brand) && is_array($brand)) {
    $_logoUrl = $brand['logo_url'] ?? '';
    $_altText = $brand['name'] ?? $brand['brand_name'] ?? 'TalentQX';
} elseif (isset($brandName)) {
    $_logoUrl = $brandName === 'Octopus AI'
        ? config('brands.brands.octopus.logo_url', 'https://talentqx.com/assets/octopus-logo-email.png')
        : config('brands.brands.talentqx.logo_url', 'https://talentqx.com/assets/logo-email.png');
    $_altText = $brandName;
} else {
    $_logoUrl = config('brands.brands.talentqx.logo_url', 'https://talentqx.com/assets/logo-email.png');
}
@endphp
@if($_logoUrl)<img src="{{ $_logoUrl }}" alt="{{ $_altText }}" style="max-height: 48px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
@endif
