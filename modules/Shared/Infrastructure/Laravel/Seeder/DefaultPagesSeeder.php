<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\Shared\Domain\Model\Page;

/**
 * Default Pages Seeder for Tenants.
 *
 * Seeds the same default pages as the central database
 * to ensure consistency across all tenant databases.
 */
class DefaultPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->command) {
            $this->command->info('Seeding default pages for tenant...');
        }

        $pages = [
            [
                'slug' => 'help-center',
                'status' => 'published',
                'order' => 1,
                'title' => [
                    'en' => 'Help Center',
                    'nl' => 'Helpcentrum',
                    'fr' => 'Centre d\'aide',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Welcome to our comprehensive help center. Here you\'ll find resources, guides, and support to help you make the most of our donation platform.</p>
                        <p>Our help center covers everything from setting up your first campaign to managing donations and understanding our security measures. Browse through our categories or use the search function to find specific information.</p>
                        <p>If you can\'t find what you\'re looking for, our customer support team is available 24/7 to assist you with any questions or concerns.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Welkom bij ons uitgebreide helpcentrum. Hier vindt u bronnen, gidsen en ondersteuning om het meeste uit ons donatieplatform te halen.</p>
                        <p>Ons helpcentrum behandelt alles van het opzetten van uw eerste campagne tot het beheren van donaties en het begrijpen van onze beveiligingsmaatregelen. Blader door onze categorieën of gebruik de zoekfunctie om specifieke informatie te vinden.</p>
                        <p>Als u niet kunt vinden wat u zoekt, staat ons klantenserviceteam 24/7 voor u klaar om u te helpen met vragen of zorgen.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Bienvenue dans notre centre d\'aide complet. Vous trouverez ici des ressources, des guides et un support pour vous aider à tirer le meilleur parti de notre plateforme de dons.</p>
                        <p>Notre centre d\'aide couvre tout, de la configuration de votre première campagne à la gestion des dons et à la compréhension de nos mesures de sécurité. Parcourez nos catégories ou utilisez la fonction de recherche pour trouver des informations spécifiques.</p>
                        <p>Si vous ne trouvez pas ce que vous cherchez, notre équipe de support client est disponible 24h/24 et 7j/7 pour vous aider avec toutes questions ou préoccupations.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'faq',
                'status' => 'published',
                'order' => 2,
                'title' => [
                    'en' => 'Frequently Asked Questions',
                    'nl' => 'Veelgestelde Vragen',
                    'fr' => 'Questions Fréquemment Posées',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Find answers to the most commonly asked questions about our donation platform, campaigns, and services.</p>
                        <h3>General Questions</h3>
                        <p>Our FAQ section covers a wide range of topics including account setup, donation processing, campaign management, and troubleshooting common issues.</p>
                        <p>We regularly update these questions based on user feedback and new features. If your question isn\'t answered here, please contact our support team.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Vind antwoorden op de meest gestelde vragen over ons donatieplatform, campagnes en diensten.</p>
                        <h3>Algemene Vragen</h3>
                        <p>Onze FAQ-sectie behandelt een breed scala aan onderwerpen, waaronder accountinstellingen, donatieverwerking, campagnebeheer en het oplossen van veelvoorkomende problemen.</p>
                        <p>We werken deze vragen regelmatig bij op basis van gebruikersfeedback en nieuwe functies. Als uw vraag hier niet wordt beantwoord, neem dan contact op met ons supportteam.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Trouvez des réponses aux questions les plus fréquemment posées sur notre plateforme de dons, campagnes et services.</p>
                        <h3>Questions Générales</h3>
                        <p>Notre section FAQ couvre un large éventail de sujets, y compris la configuration de compte, le traitement des dons, la gestion de campagne et le dépannage des problèmes courants.</p>
                        <p>Nous mettons régulièrement à jour ces questions basées sur les commentaires des utilisateurs et les nouvelles fonctionnalités. Si votre question n\'est pas répondue ici, veuillez contacter notre équipe de support.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'contact',
                'status' => 'published',
                'order' => 3,
                'title' => [
                    'en' => 'Contact Us',
                    'nl' => 'Contact',
                    'fr' => 'Nous Contacter',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>We\'re here to help! Get in touch with our team for support, feedback, or general inquiries about our donation platform.</p>
                        <p>Our customer support team is available 24/7 to assist you with any questions about campaigns, donations, or technical issues. We aim to respond to all inquiries within 24 hours.</p>
                        <p>For urgent matters or technical emergencies, please use our priority contact channels. We also welcome feedback and suggestions to help us improve our services.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>We zijn er om te helpen! Neem contact op met ons team voor ondersteuning, feedback of algemene vragen over ons donatieplatform.</p>
                        <p>Ons klantenserviceteam is 24/7 beschikbaar om u te helpen met vragen over campagnes, donaties of technische problemen. We streven ernaar om binnen 24 uur op alle vragen te reageren.</p>
                        <p>Voor urgente zaken of technische noodgevallen kunt u onze prioritaire contactkanalen gebruiken. We verwelkomen ook feedback en suggesties om onze diensten te verbeteren.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Nous sommes là pour vous aider ! Contactez notre équipe pour du support, des commentaires ou des questions générales sur notre plateforme de dons.</p>
                        <p>Notre équipe de support client est disponible 24h/24 et 7j/7 pour vous aider avec toutes questions sur les campagnes, dons ou problèmes techniques. Nous visons à répondre à toutes les demandes dans les 24 heures.</p>
                        <p>Pour les questions urgentes ou les urgences techniques, veuillez utiliser nos canaux de contact prioritaires. Nous accueillons également les commentaires et suggestions pour nous aider à améliorer nos services.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'csr-guidelines',
                'status' => 'published',
                'order' => 4,
                'title' => [
                    'en' => 'Corporate Social Responsibility Guidelines',
                    'nl' => 'Richtlijnen voor Maatschappelijk Verantwoord Ondernemen',
                    'fr' => 'Directives de Responsabilité Sociale d\'Entreprise',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Our comprehensive CSR guidelines help organizations create meaningful impact through strategic charitable giving and employee engagement programs.</p>
                        <p>These guidelines cover best practices for campaign development, employee participation strategies, impact measurement, and transparent reporting. We believe that effective CSR programs should align with business values while creating genuine positive change in communities.</p>
                        <p>Whether you\'re starting your first CSR initiative or enhancing existing programs, our guidelines provide practical frameworks and proven strategies for success.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Onze uitgebreide MVO-richtlijnen helpen organisaties om betekenisvolle impact te creëren door strategische liefdadigheidsdonaties en medewerkerbetrokkenheidsprogramma\'s.</p>
                        <p>Deze richtlijnen behandelen best practices voor campagneontwikkeling, strategieën voor werknemersparticipatie, impactmeting en transparante rapportage. Wij geloven dat effectieve MVO-programma\'s moeten aansluiten bij bedrijfswaarden terwijl ze echte positieve verandering in gemeenschappen creëren.</p>
                        <p>Of u nu uw eerste MVO-initiatief start of bestaande programma\'s verbetert, onze richtlijnen bieden praktische kaders en bewezen strategieën voor succes.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Nos directives RSE complètes aident les organisations à créer un impact significatif grâce à des dons caritatifs stratégiques et des programmes d\'engagement des employés.</p>
                        <p>Ces directives couvrent les meilleures pratiques pour le développement de campagnes, les stratégies de participation des employés, la mesure d\'impact et les rapports transparents. Nous croyons que les programmes RSE efficaces doivent s\'aligner avec les valeurs d\'entreprise tout en créant un changement positif authentique dans les communautés.</p>
                        <p>Que vous commenciez votre première initiative RSE ou amélioriez des programmes existants, nos directives fournissent des cadres pratiques et des stratégies éprouvées pour le succès.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'about',
                'status' => 'published',
                'order' => 5,
                'title' => [
                    'en' => 'About Us',
                    'nl' => 'Over Ons',
                    'fr' => 'À Propos de Nous',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>We are a leading platform dedicated to empowering organizations and their employees to make a meaningful difference through strategic charitable giving and community engagement.</p>
                        <p>Founded with the vision of bridging the gap between corporate social responsibility and grassroots community impact, we provide innovative tools and resources that make giving simple, transparent, and effective.</p>
                        <p>Our mission is to create lasting positive change by connecting passionate employees with causes they care about, while helping organizations build stronger, more engaged communities both internally and externally.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Wij zijn een toonaangevend platform dat zich toelegt op het empoweren van organisaties en hun werknemers om een betekenisvol verschil te maken door strategische liefdadigheidsdonaties en gemeenschapsbetrokkenheid.</p>
                        <p>Opgericht met de visie om de kloof tussen maatschappelijk verantwoord ondernemen en grassroots gemeenschapsimpact te overbruggen, bieden wij innovatieve tools en bronnen die geven eenvoudig, transparant en effectief maken.</p>
                        <p>Onze missie is om blijvende positieve verandering te creëren door gepassioneerde werknemers te verbinden met doelen waar zij om geven, terwijl we organisaties helpen sterkere, meer betrokken gemeenschappen te bouwen, zowel intern als extern.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Nous sommes une plateforme leader dédiée à l\'autonomisation des organisations et de leurs employés pour faire une différence significative grâce aux dons caritatifs stratégiques et à l\'engagement communautaire.</p>
                        <p>Fondés avec la vision de combler le fossé entre la responsabilité sociale des entreprises et l\'impact communautaire de base, nous fournissons des outils et ressources innovants qui rendent les dons simples, transparents et efficaces.</p>
                        <p>Notre mission est de créer un changement positif durable en connectant des employés passionnés avec des causes qui leur tiennent à cœur, tout en aidant les organisations à construire des communautés plus fortes et plus engagées, tant en interne qu\'en externe.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'sustainability',
                'status' => 'published',
                'order' => 6,
                'title' => [
                    'en' => 'Sustainability Commitment',
                    'nl' => 'Duurzaamheidstoewijding',
                    'fr' => 'Engagement de Durabilité',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Our commitment to sustainability extends beyond just environmental considerations to encompass social and economic sustainability in all our operations and partnerships.</p>
                        <p>We actively work to minimize our environmental footprint through digital-first operations, carbon-neutral hosting, and partnerships with environmentally conscious organizations. Our platform promotes sustainable giving practices and supports campaigns focused on environmental protection.</p>
                        <p>We believe that true sustainability requires long-term thinking, transparent reporting, and continuous improvement in how we operate and serve our community of users.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Onze toewijding aan duurzaamheid strekt zich uit verder dan alleen milieuconsideraties om sociale en economische duurzaamheid in al onze operaties en partnerschappen te omvatten.</p>
                        <p>We werken actief aan het minimaliseren van onze ecologische voetafdruk door digitaal-eerste operaties, klimaatneutrale hosting en partnerschappen met milieubewuste organisaties. Ons platform bevordert duurzame geefpraktijken en ondersteunt campagnes gericht op milieubescherming.</p>
                        <p>We geloven dat echte duurzaamheid langetermijndenken, transparante rapportage en continue verbetering vereist in hoe we opereren en onze gemeenschap van gebruikers dienen.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Notre engagement envers la durabilité s\'étend au-delà des seules considérations environnementales pour englober la durabilité sociale et économique dans toutes nos opérations et partenariats.</p>
                        <p>Nous travaillons activement à minimiser notre empreinte environnementale grâce à des opérations numériques d\'abord, un hébergement neutre en carbone et des partenariats avec des organisations soucieuses de l\'environnement. Notre plateforme promeut des pratiques de don durables et soutient des campagnes axées sur la protection environnementale.</p>
                        <p>Nous croyons que la vraie durabilité nécessite une réflexion à long terme, des rapports transparents et une amélioration continue dans la façon dont nous opérons et servons notre communauté d\'utilisateurs.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'blog',
                'status' => 'published',
                'order' => 7,
                'title' => [
                    'en' => 'Blog & Insights',
                    'nl' => 'Blog & Inzichten',
                    'fr' => 'Blog & Insights',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Stay informed with our latest insights, success stories, and expert advice on corporate social responsibility, employee engagement, and effective charitable giving strategies.</p>
                        <p>Our blog features in-depth articles from industry leaders, case studies of successful campaigns, and practical tips for maximizing the impact of your giving programs. We cover topics ranging from digital transformation in philanthropy to measuring social impact.</p>
                        <p>Subscribe to our newsletter to receive the latest updates and never miss important insights that can help improve your organization\'s social impact initiatives.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Blijf geïnformeerd met onze laatste inzichten, succesverhalen en deskundig advies over maatschappelijk verantwoord ondernemen, werknemersbetrokkenheid en effectieve liefdadigheidsdonatiestrategieën.</p>
                        <p>Onze blog bevat diepgaande artikelen van industrieleiders, casestudies van succesvolle campagnes en praktische tips voor het maximaliseren van de impact van uw geefprogramma\'s. We behandelen onderwerpen variërend van digitale transformatie in filantropie tot het meten van sociale impact.</p>
                        <p>Abonneer u op onze nieuwsbrief om de laatste updates te ontvangen en belangrijke inzichten die kunnen helpen uw organisatie\'s sociale impactinitiatieven te verbeteren nooit te missen.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Restez informé avec nos derniers insights, histoires de succès et conseils d\'experts sur la responsabilité sociale des entreprises, l\'engagement des employés et les stratégies de dons caritatifs efficaces.</p>
                        <p>Notre blog présente des articles approfondis de leaders de l\'industrie, des études de cas de campagnes réussies et des conseils pratiques pour maximiser l\'impact de vos programmes de dons. Nous couvrons des sujets allant de la transformation numérique en philanthropie à la mesure de l\'impact social.</p>
                        <p>Abonnez-vous à notre newsletter pour recevoir les dernières mises à jour et ne jamais manquer d\'insights importants qui peuvent aider à améliorer les initiatives d\'impact social de votre organisation.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'employee-resources',
                'status' => 'published',
                'order' => 8,
                'title' => [
                    'en' => 'Employee Resources',
                    'nl' => 'Werknemersbronnen',
                    'fr' => 'Ressources Employés',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Comprehensive resources designed to help employees maximize their impact and engagement through our charitable giving platform.</p>
                        <p>Our employee resource center includes step-by-step guides for creating campaigns, best practices for team fundraising, tools for tracking personal giving impact, and templates for organizing workplace volunteer events.</p>
                        <p>We also provide training materials, webinar recordings, and downloadable resources that employees can use to become more effective advocates for causes they care about within their organizations.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Uitgebreide bronnen ontworpen om werknemers te helpen hun impact en betrokkenheid te maximaliseren door ons liefdadigheidsdonatieplatform.</p>
                        <p>Ons werknemersbronncentrum bevat stap-voor-stap gidsen voor het creëren van campagnes, best practices voor teamfundraising, tools voor het volgen van persoonlijke geefimpact en sjablonen voor het organiseren van werkplekvolunteer evenementen.</p>
                        <p>We bieden ook trainingsmaterialen, webinar-opnames en downloadbare bronnen die werknemers kunnen gebruiken om effectievere voorstanders te worden voor doelen waar zij om geven binnen hun organisaties.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Ressources complètes conçues pour aider les employés à maximiser leur impact et engagement grâce à notre plateforme de dons caritatifs.</p>
                        <p>Notre centre de ressources pour employés inclut des guides étape par étape pour créer des campagnes, les meilleures pratiques pour la collecte de fonds d\'équipe, des outils pour suivre l\'impact de dons personnels et des modèles pour organiser des événements de bénévolat sur le lieu de travail.</p>
                        <p>Nous fournissons également du matériel de formation, des enregistrements de webinaires et des ressources téléchargeables que les employés peuvent utiliser pour devenir des défenseurs plus efficaces des causes qui leur tiennent à cœur dans leurs organisations.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'privacy',
                'status' => 'published',
                'order' => 9,
                'title' => [
                    'en' => 'Privacy Policy',
                    'nl' => 'Privacybeleid',
                    'fr' => 'Politique de Confidentialité',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>We are committed to protecting your privacy and ensuring the security of your personal information. This privacy policy explains how we collect, use, and safeguard your data when you use our donation platform.</p>
                        <p>We collect only the information necessary to provide our services effectively, including account details, donation history, and campaign participation. All data is encrypted and stored securely in compliance with international privacy regulations including GDPR and CCPA.</p>
                        <p>We never sell your personal information to third parties and only share data with trusted partners when necessary to process donations or improve our services, always with your explicit consent.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>We zijn toegewijd aan het beschermen van uw privacy en het waarborgen van de veiligheid van uw persoonlijke informatie. Dit privacybeleid legt uit hoe we uw gegevens verzamelen, gebruiken en beschermen wanneer u ons donatieplatform gebruikt.</p>
                        <p>We verzamelen alleen de informatie die nodig is om onze diensten effectief te leveren, inclusief accountdetails, donatiegeschiedenis en campagneparticipatie. Alle gegevens worden versleuteld en veilig opgeslagen in overeenstemming met internationale privacyregels inclusief GDPR en CCPA.</p>
                        <p>We verkopen uw persoonlijke informatie nooit aan derden en delen alleen gegevens met vertrouwde partners wanneer noodzakelijk om donaties te verwerken of onze diensten te verbeteren, altijd met uw expliciete toestemming.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Nous nous engageons à protéger votre vie privée et à assurer la sécurité de vos informations personnelles. Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos données lorsque vous utilisez notre plateforme de dons.</p>
                        <p>Nous collectons uniquement les informations nécessaires pour fournir efficacement nos services, y compris les détails du compte, l\'historique des dons et la participation aux campagnes. Toutes les données sont cryptées et stockées en sécurité en conformité avec les réglementations internationales de confidentialité incluant GDPR et CCPA.</p>
                        <p>Nous ne vendons jamais vos informations personnelles à des tiers et ne partageons les données qu\'avec des partenaires de confiance lorsque nécessaire pour traiter les dons ou améliorer nos services, toujours avec votre consentement explicite.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'terms',
                'status' => 'published',
                'order' => 10,
                'title' => [
                    'en' => 'Terms of Service',
                    'nl' => 'Servicevoorwaarden',
                    'fr' => 'Conditions de Service',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>These terms of service govern your use of our donation platform and outline the rights and responsibilities of all users, organizations, and service providers.</p>
                        <p>By using our platform, you agree to these terms and commit to using our services responsibly and in compliance with all applicable laws. We reserve the right to suspend or terminate accounts that violate these terms or engage in fraudulent activities.</p>
                        <p>Our terms cover user conduct, payment processing, data usage, intellectual property rights, and dispute resolution procedures. We regularly update these terms to reflect changes in law and platform features.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Deze servicevoorwaarden regelen uw gebruik van ons donatieplatform en schetsen de rechten en verantwoordelijkheden van alle gebruikers, organisaties en serviceverleners.</p>
                        <p>Door ons platform te gebruiken, gaat u akkoord met deze voorwaarden en verbindt u zich ertoe onze diensten verantwoordelijk te gebruiken en in overeenstemming met alle toepasselijke wetten. We behouden ons het recht voor om accounts op te schorten of te beëindigen die deze voorwaarden overtreden of zich bezighouden met frauduleuze activiteiten.</p>
                        <p>Onze voorwaarden dekken gebruikersgedrag, betalingsverwerking, gegevensgebruik, intellectuele eigendomsrechten en geschillenbeslechtingsprocedures. We werken deze voorwaarden regelmatig bij om veranderingen in wet en platformfuncties weer te geven.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Ces conditions de service régissent votre utilisation de notre plateforme de dons et décrivent les droits et responsabilités de tous les utilisateurs, organisations et fournisseurs de services.</p>
                        <p>En utilisant notre plateforme, vous acceptez ces conditions et vous engagez à utiliser nos services de manière responsable et en conformité avec toutes les lois applicables. Nous nous réservons le droit de suspendre ou résilier les comptes qui violent ces conditions ou s\'engagent dans des activités frauduleuses.</p>
                        <p>Nos conditions couvrent la conduite des utilisateurs, le traitement des paiements, l\'utilisation des données, les droits de propriété intellectuelle et les procédures de résolution des conflits. Nous mettons régulièrement à jour ces conditions pour refléter les changements dans la loi et les fonctionnalités de la plateforme.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'cookies',
                'status' => 'published',
                'order' => 11,
                'title' => [
                    'en' => 'Cookie Policy',
                    'nl' => 'Cookiebeleid',
                    'fr' => 'Politique des Cookies',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>This cookie policy explains how we use cookies and similar technologies to enhance your experience on our donation platform and provide personalized services.</p>
                        <p>We use essential cookies for platform functionality, analytics cookies to understand user behavior and improve our services, and preference cookies to remember your settings and language choices. All non-essential cookies require your explicit consent.</p>
                        <p>You can manage your cookie preferences at any time through your browser settings or our cookie management center. Disabling certain cookies may affect platform functionality and your user experience.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Dit cookiebeleid legt uit hoe we cookies en vergelijkbare technologieën gebruiken om uw ervaring op ons donatieplatform te verbeteren en gepersonaliseerde diensten te bieden.</p>
                        <p>We gebruiken essentiële cookies voor platformfunctionaliteit, analytics cookies om gebruikersgedrag te begrijpen en onze diensten te verbeteren, en voorkeursinstelling cookies om uw instellingen en taalkeuzes te onthouden. Alle niet-essentiële cookies vereisen uw expliciete toestemming.</p>
                        <p>U kunt uw cookievoorkeuren op elk moment beheren via uw browserinstellingen of ons cookiebeheerscentrum. Het uitschakelen van bepaalde cookies kan de platformfunctionaliteit en uw gebruikerservaring beïnvloeden.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Cette politique des cookies explique comment nous utilisons les cookies et technologies similaires pour améliorer votre expérience sur notre plateforme de dons et fournir des services personnalisés.</p>
                        <p>Nous utilisons des cookies essentiels pour la fonctionnalité de la plateforme, des cookies d\'analyse pour comprendre le comportement des utilisateurs et améliorer nos services, et des cookies de préférence pour mémoriser vos paramètres et choix de langue. Tous les cookies non essentiels nécessitent votre consentement explicite.</p>
                        <p>Vous pouvez gérer vos préférences de cookies à tout moment via les paramètres de votre navigateur ou notre centre de gestion des cookies. Désactiver certains cookies peut affecter la fonctionnalité de la plateforme et votre expérience utilisateur.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'security',
                'status' => 'published',
                'order' => 12,
                'title' => [
                    'en' => 'Security & Data Protection',
                    'nl' => 'Beveiliging & Gegevensbescherming',
                    'fr' => 'Sécurité & Protection des Données',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>Security is at the core of everything we do. We implement industry-leading security measures to protect your data, donations, and personal information from unauthorized access and cyber threats.</p>
                        <p>Our security infrastructure includes end-to-end encryption, multi-factor authentication, regular security audits, and compliance with international security standards including ISO 27001 and SOC 2 Type II.</p>
                        <p>We maintain 24/7 security monitoring, conduct regular penetration testing, and have established incident response procedures to quickly address any security concerns that may arise.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>Beveiliging staat centraal in alles wat we doen. We implementeren toonaangevende beveiligingsmaatregelen om uw gegevens, donaties en persoonlijke informatie te beschermen tegen ongeautoriseerde toegang en cyberdreigingen.</p>
                        <p>Onze beveiligingsinfrastructuur omvat end-to-end encryptie, multi-factor authenticatie, regelmatige beveiligingsaudits en naleving van internationale beveiligingsstandaarden inclusief ISO 27001 en SOC 2 Type II.</p>
                        <p>We onderhouden 24/7 beveiligingsmonitoring, voeren regelmatig penetratietests uit en hebben incidentresponsprotocollen vastgesteld om snel eventuele beveiligingsproblemen aan te pakken die zich kunnen voordoen.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>La sécurité est au cœur de tout ce que nous faisons. Nous implémentons des mesures de sécurité de pointe pour protéger vos données, dons et informations personnelles contre l\'accès non autorisé et les menaces cybernétiques.</p>
                        <p>Notre infrastructure de sécurité inclut le chiffrement de bout en bout, l\'authentification multi-facteurs, des audits de sécurité réguliers et la conformité aux standards de sécurité internationaux incluant ISO 27001 et SOC 2 Type II.</p>
                        <p>Nous maintenons une surveillance de sécurité 24h/24 et 7j/7, effectuons des tests de pénétration réguliers et avons établi des procédures de réponse aux incidents pour traiter rapidement toute préoccupation de sécurité qui pourrait survenir.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'accessibility',
                'status' => 'published',
                'order' => 13,
                'title' => [
                    'en' => 'Accessibility Statement',
                    'nl' => 'Toegankelijkheidsverklaring',
                    'fr' => 'Déclaration d\'Accessibilité',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>We are committed to ensuring digital accessibility for people with disabilities. We continually improve the user experience for everyone and apply relevant accessibility standards.</p>
                        <p>Our platform complies with WCAG 2.1 Level AA guidelines and includes features such as keyboard navigation, screen reader compatibility, high contrast modes, and adjustable text sizes to accommodate users with various accessibility needs.</p>
                        <p>We regularly conduct accessibility audits and user testing with people with disabilities to identify and address barriers. If you encounter any accessibility issues, please contact our support team for assistance.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>We zijn toegewijd aan het waarborgen van digitale toegankelijkheid voor mensen met een beperking. We verbeteren voortdurend de gebruikerservaring voor iedereen en passen relevante toegankelijkheidsstandaarden toe.</p>
                        <p>Ons platform voldoet aan WCAG 2.1 Level AA richtlijnen en bevat functies zoals toetsenbordnavigatie, screen reader compatibiliteit, hoog contrast modi en aanpasbare tekst groottes om gebruikers met verschillende toegankelijkheidsbehoeften te accommoderen.</p>
                        <p>We voeren regelmatig toegankelijkheidsaudits en gebruikerstests uit met mensen met een beperking om barrières te identificeren en aan te pakken. Als u toegankelijkheidsproblemen ondervindt, neem dan contact op met ons supportteam voor hulp.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Nous nous engageons à assurer l\'accessibilité numérique pour les personnes handicapées. Nous améliorons continuellement l\'expérience utilisateur pour tous et appliquons les standards d\'accessibilité pertinents.</p>
                        <p>Notre plateforme se conforme aux directives WCAG 2.1 Niveau AA et inclut des fonctionnalités telles que la navigation au clavier, la compatibilité avec les lecteurs d\'écran, les modes de contraste élevé et les tailles de texte ajustables pour accommoder les utilisateurs avec divers besoins d\'accessibilité.</p>
                        <p>Nous effectuons régulièrement des audits d\'accessibilité et des tests utilisateur avec des personnes handicapées pour identifier et traiter les barrières. Si vous rencontrez des problèmes d\'accessibilité, veuillez contacter notre équipe de support pour assistance.</p>
                    </div>',
                ],
            ],
            [
                'slug' => 'compliance',
                'status' => 'published',
                'order' => 14,
                'title' => [
                    'en' => 'Regulatory Compliance',
                    'nl' => 'Regelgevingsnaleving',
                    'fr' => 'Conformité Réglementaire',
                ],
                'content' => [
                    'en' => '<div class="prose max-w-none">
                        <p>We maintain strict compliance with all applicable regulations governing charitable giving, data protection, financial transactions, and corporate social responsibility reporting.</p>
                        <p>Our compliance framework covers anti-money laundering (AML) requirements, know your customer (KYC) procedures, GDPR and CCPA data protection regulations, and charity commission guidelines across multiple jurisdictions.</p>
                        <p>We work with legal and compliance experts to ensure our platform meets evolving regulatory requirements and provide transparency reports to demonstrate our commitment to ethical and legal operations.</p>
                    </div>',
                    'nl' => '<div class="prose max-w-none">
                        <p>We handhaven strikte naleving van alle toepasselijke regelgeving betreffende liefdadigheidsdonaties, gegevensbescherming, financiële transacties en rapportage van maatschappelijk verantwoord ondernemen.</p>
                        <p>Ons compliance-kader omvat anti-witwas (AML) vereisten, ken-uw-klant (KYC) procedures, GDPR en CCPA gegevensbeschermingsregels en liefdadigheidscommissie richtlijnen in meerdere jurisdicties.</p>
                        <p>We werken samen met juridische en compliance-experts om ervoor te zorgen dat ons platform voldoet aan evoluerende regelgevingsvereisten en bieden transparantierapporten om onze toewijding aan ethische en legale operaties te demonstreren.</p>
                    </div>',
                    'fr' => '<div class="prose max-w-none">
                        <p>Nous maintenons une stricte conformité avec toutes les réglementations applicables régissant les dons caritatifs, la protection des données, les transactions financières et les rapports de responsabilité sociale des entreprises.</p>
                        <p>Notre cadre de conformité couvre les exigences anti-blanchiment d\'argent (AML), les procédures de connaissance du client (KYC), les réglementations de protection des données GDPR et CCPA, et les directives de commission caritative dans plusieurs juridictions.</p>
                        <p>Nous travaillons avec des experts juridiques et de conformité pour nous assurer que notre plateforme répond aux exigences réglementaires évolutives et fournissons des rapports de transparence pour démontrer notre engagement envers des opérations éthiques et légales.</p>
                    </div>',
                ],
            ],
        ];

        foreach ($pages as $pageData) {
            Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData,
            );
        }

        if ($this->command) {
            $this->command->info('Successfully seeded ' . count($pages) . ' default pages');
        }
    }
}
