<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;

class AzGenericTemplateSeeder extends Seeder
{
    public function run(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'az', 'position_code' => '__generic__'],
            [
                'title' => 'Generic Interview Template (Azerbaijani)',
                'template_json' => json_encode([
                    'version' => 'v1',
                    'language' => 'az',
                    'generic_template' => [
                        'questions' => [
                            [
                                'slot' => 1,
                                'competency' => 'communication',
                                'question' => 'Mürəkkəb bir mövzunu sadə şəkildə izah etməli olduğunuz bir vəziyyəti təsvir edə bilərsinizmi? Nə etdiniz və nəticə nə oldu?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'İzah edə bilmədi, dinləyici perspektivi yox, qarışıq və dağınıq təqdimat',
                                    '2' => 'Əsas məlumat ötürüldü amma strukturlaşdırılmamış, dinləyiciyə uyğunlaşma yox',
                                    '3' => 'Aydın izahat, əsas struktur var, əks əlaqəyə açıq',
                                    '4' => 'Aydın və mütəşəkkil izahat, dinləyici səviyyəsinə uyğunlaşma, suallara açıq yanaşma',
                                    '5' => 'Mükəmməl strukturlaşdırma, empatik dinləyici yönümlü izahat, effektiv əks əlaqə dövrü',
                                ],
                                'positive_signals' => [
                                    'Dinləyicinin bilik səviyyəsini soruşdu',
                                    'Nümunələr və bənzətmələr istifadə etdi',
                                    'Başa düşüldüyünə əmin olmaq üçün yoxladı',
                                    'Əks əlaqəyə əsasən yanaşmasını dəyişdi',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Ünsiyyət məsuliyyətindən qaçma ifadələri: "bu mənim işim deyil", "başqası həll etsin"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 2,
                                'competency' => 'accountability',
                                'question' => 'İş yerinizdə səhv etdiyiniz və ya bir şeyin səhv getdiyi bir vəziyyəti təsvir edə bilərsinizmi? Bu vəziyyəti necə həll etdiniz?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Səhvi inkar etdi və ya tamamilə başqalarını günahlandırdı, məsuliyyət götürmədi',
                                    '2' => 'Səhvi qəbul etdi amma düzəltmək üçün heç nə etmədi, passiv qaldı',
                                    '3' => 'Səhvi qəbul etdi və əsas düzəldici addımlar atdı',
                                    '4' => 'Tam məsuliyyət götürdü, proaktiv həll yolu tapdı, maraqlı tərəfləri məlumatlandırdı',
                                    '5' => 'Səhvi sahihləndi, sistematik həll yolu hazırladı, prosesin təkmilləşdirilməsini təklif etdi',
                                ],
                                'positive_signals' => [
                                    'Səhvi açıq şəkildə qəbul etdi',
                                    'Başqalarını günahlandırmadı',
                                    'Konkret düzəldici addımları təsvir etdi',
                                    'Öyrənilmiş dərsləri paylaşdı',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Daima xarici amillərə istinad: "komanda dəstəkləmədi", "rəhbər yanlış istiqamətləndirdi"',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Hekayədə uyğunsuzluqlar: əvvəlcə başqasını günahlandırıb sonra sahihlənmə, ziddiyyətli detallar',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 3,
                                'competency' => 'teamwork',
                                'question' => 'Fərqli baxış bucaqlarına sahib komanda üzvləri ilə birlikdə işlədiyiniz bir layihəni təsvir edə bilərsinizmi? Fərqli perspektivləri necə idarə etdiniz?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Komanda işindən qaçdı və ya öz fikrini tətbiq etdi, razılıq axtarmadı',
                                    '2' => 'Passiv iştirak, fikirlərini ifadə etmədi və ya münaqişəni görməzdən gəldi',
                                    '3' => 'Fərqli fikirləri dinlədi, əsas razılıq səylərini göstərdi',
                                    '4' => 'Aktiv şəkildə fərqli perspektivləri inteqrasiya etdi, konstruktiv müzakirə mühiti yaratdı',
                                    '5' => 'Fərqliliklərdən sinergiya yaratdı, hər kəsin iştirakını təmin etdi, ortaq məqsədə yönəltdi',
                                ],
                                'positive_signals' => [
                                    'Başqalarının fikirlərini aktiv şəkildə soruşdu',
                                    'Öz fikrini dəyişməyə açıq idi',
                                    'Münaqişəni konstruktiv şəkildə idarə etdi',
                                    'Komanda uğurunu fərdi uğurdan üstün tutdu',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Komanda uğurunu sahihlənmə: "əslində mənim fikrimi tətbiq etdilər", "mənsiz edə bilməzdilər"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AGGRESSION',
                                        'trigger_guidance' => 'Komanda üzvlərinə qarşı alçaldıcı ifadələr: təhqir, şəxsi hücumlar, qəzəbli ton',
                                        'severity' => 'critical',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 4,
                                'competency' => 'stress_resilience',
                                'question' => 'İntensiv təzyiq altında eyni anda bir neçə prioritet ilə işlədiyiniz bir dövrü təsvir edə bilərsinizmi? Necə öhdəsindən gəldiniz?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Stres qarşısında çökdü, işləri tamamlaya bilmədi, panik və ya qaçma davranışı',
                                    '2' => 'Çətinliklə tamamladı, stres idarəetmə strategiyası yox, reaktiv yanaşma',
                                    '3' => 'İşləri tamamladı, əsas prioritetləşdirmə etdi, orta səviyyədə stres idarəetməsi',
                                    '4' => 'Effektiv prioritetləşdirmə, sakit qalaraq sistematik yanaşma, keyfiyyəti qoruyaraq tamamladı',
                                    '5' => 'Təzyiq altında üstün performans, başqalarını da sakitləşdirdi, stresi motivasiya kimi istifadə etdi',
                                ],
                                'positive_signals' => [
                                    'Konkret prioritetləşdirmə metodu təsvir etdi',
                                    'Emosional nəzarəti qoruduğunu göstərdi',
                                    'Lazım olanda kömək istədi',
                                    'Gələcək üçün dərs çıxardı',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_UNSTABLE',
                                        'trigger_guidance' => 'Stres qarşısında nəzarətsiz reaksiyalar: "partladım", "hər şeyi buraxıb getdim", "tamamilə nəzarətimi itirdim"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Stresli vəziyyətlərdən sistemik qaçma: "belə işlər mənim işim deyil", "bu cür məsuliyyət götürmürəm"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 5,
                                'competency' => 'adaptability',
                                'question' => 'İş yerinizdə gözlənilməz bir dəyişiklik baş verdikdə necə uyğunlaşdınız? Nümunə verə bilərsinizmi?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Dəyişikliyə müqavimət göstərdi, uyğunlaşmadı, şikayətçi və ya mane olan davranış',
                                    '2' => 'Məcburi uyğunlaşdı, neqativ münasibəti qorudu',
                                    '3' => 'Dəyişikliyi qəbul etdi, ağlabatan müddətdə uyğunlaşdı',
                                    '4' => 'Dəyişikliyi tez qəbul etdi, yeni vəziyyətdə səmərəli işlədi, başqalarının uyğunlaşmasına kömək etdi',
                                    '5' => 'Dəyişikliyi fürsətə çevirdi, proaktiv təkliflər verdi, dəyişiklik lideri rolunu öhdəsinə götürdü',
                                ],
                                'positive_signals' => [
                                    'Dəyişikliyin səbəbini anlamağa çalışdı',
                                    'Tez yeni bacarıqlar əldə etdi',
                                    'Müsbət münasibəti qorudu',
                                    'Başqalarının uyğunlaşmasına dəstək oldu',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Dəyişiklikdən qaçma və rədd etmə: "belə şeylər etmirəm", "yeni sistem öyrənmək mənim işim deyil"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 6,
                                'competency' => 'learning_agility',
                                'question' => 'Tamamilə yeni bir mövzu və ya bacarığı qısa müddətdə öyrənməli olduğunuz bir vəziyyəti təsvir edə bilərsinizmi? Necə yanaşdınız?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Öyrənmə istəksizliyi, passiv münasibət, başqalarından asılı qaldı',
                                    '2' => 'Əsas səviyyədə öyrəndi amma dərinləşə bilmədi, yalnız zəruri olanı etdi',
                                    '3' => 'Aktiv öyrənmə səyi, standart resurslardan istifadə etdi, ağlabatan müddətdə öyrəndi',
                                    '4' => 'Sürətli və effektiv öyrənmə, bir neçə resursdan istifadə, öyrəndiklərini dərhal tətbiq etdi',
                                    '5' => 'Üstün öyrənmə sürəti, öyrəndiklərini təkmilləşdirdi, başqalarını da öyrətdi',
                                ],
                                'positive_signals' => [
                                    'Bir neçə öyrənmə resursundan istifadə etdi',
                                    'Sual verməkdən çəkinmədi',
                                    'Öyrəndiklərini praktikada tətbiq etdiyini danışdı',
                                    'Öyrənmə prosesindən həzz aldığını bildirdi',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Öyrənmə məsuliyyətindən qaçma: "yeni şeylər öyrənmək mənim işim deyil", "başqası öyrətsin"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 7,
                                'competency' => 'integrity',
                                'question' => 'Etik baxımdan çətin bir qərarla üzləşdiyiniz bir vəziyyəti təsvir edə bilərsinizmi? Necə davrandınız?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Qeyri-etik davranış təsvir etdi və ya qaydaları əyməyi normallaşdırdı',
                                    '2' => 'Etik dilemmanı tanıdı amma hərəkətə keçmədi, passiv qaldı',
                                    '3' => 'Doğru olanı etdi amma yalnız tələb olunduğu üçün, daxili motivasiya belirsiz',
                                    '4' => 'Etik prinsiplərə sadiq qaldı, çətin vəziyyətdə belə düzgün qərar verdi, ardıcıl davranış',
                                    '5' => 'Etik liderlik göstərdi, başqalarını düzgün davranışa yönəltdi, doğru olanı müdafiə etmək üçün risk aldı',
                                ],
                                'positive_signals' => [
                                    'Aydın və ardıcıl etik çərçivə təsvir etdi',
                                    'Şəxsi xərcə baxmayaraq doğru olanı etdi',
                                    'Şəffaflıq və düzgünlüyü vurğuladı',
                                    'Qeyri-etik təzyiqə müqavimət göstərdi',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Etik uyğunsuzluq: vəziyyətə görə dəyişən qaydalar, "hamı edir" normallaşdırması',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Etik pozuntular üçün başqalarını günahlandırma: "rəhbər məni məcbur etdi", "sistem belə qurulub"',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 8,
                                'competency' => 'role_competence',
                                'question' => 'Bu vəzifənin əsas tələblərindən birini yerinə yetirdiyiniz bir təcrübəni təsvir edə bilərsinizmi? Hansı yanaşmanı istifadə etdiniz və nəticə nə oldu?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Müvafiq təcrübə yox və ya çox səthi, əsas tələbləri anlamadığını göstərdi',
                                    '2' => 'Məhdud təcrübə, əsas konsepsiyaları bilir amma tətbiqdə zəif',
                                    '3' => 'Kifayət qədər təcrübə, standart prosesləri düzgün tətbiq etdi, qəbul edilə bilən nəticələr',
                                    '4' => 'Güclü təcrübə, keyfiyyətli və ölçülə bilən nəticələr verdi, prosesi təkmilləşdirdi',
                                    '5' => 'Üstün performans, innovativ yanaşmalar hazırladı, başqalarını öyrədə biləcək səviyyədə',
                                ],
                                'positive_signals' => [
                                    'Konkret və ölçülə bilən nəticələr paylaşdı',
                                    'Proses addımlarını düzgün və məntiqi sıraladı',
                                    'Problemləri necə həll etdiyini izah etdi',
                                    'Davamlı inkişaf nümunələri verdi',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Səriştə şişirtməsi: detal soruşulduqda uyğunsuzluqlar, izahat istənildikdə qeyri-müəyyən cavablar',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Qeyri-real özgüvən: "bu işi ən yaxşı mən edirəm", "heç kəs mənim qədər bilmir"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'positions' => [],
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('AZ __generic__ template seeded.');
    }
}
