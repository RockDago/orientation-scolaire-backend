<?php

require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../core/Response.php';

class DashboardController
{
    
    public static function trackView(): void
    {
        try {
            $body     = json_decode(file_get_contents('php://input'), true) ?? [];
            $page     = trim($body['page'] ?? '');
            $metierId = isset($body['metier_id']) ? (int) $body['metier_id'] : null;
            $visitorId = $body['visitor_id'] ?? null;
            $clientInfo = $body['client_info'] ?? null;

            if (!$page) {
                Response::json(['message' => 'Paramètre page requis'], 422);
                return;
            }

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['REMOTE_ADDR']
                ?? null;
            $ua = $clientInfo['user_agent']
                ?? $_SERVER['HTTP_USER_AGENT']
                ?? null;

            (new DashboardModel())->recordPageView($page, $metierId, $ip, $ua, $visitorId, $clientInfo);

            Response::json(['message' => 'Vue enregistrée'], 201);
        } catch (Exception $e) {
            error_log("Erreur dans trackView: " . $e->getMessage());
            Response::json(['message' => 'Erreur serveur'], 500);
        }
    }
  
    public static function trackSearch(): void
    {
        try {
            $body        = json_decode(file_get_contents('php://input'), true) ?? [];
            $metierId    = isset($body['metier_id'])    ? (int)  $body['metier_id']    : null;
            $metierLabel = isset($body['metier_label']) ? trim($body['metier_label'])  : null;
            $visitorId = $body['visitor_id'] ?? null;
            $clientInfo = $body['client_info'] ?? null;

            if (!$metierId || !$metierLabel) {
                Response::json(['message' => 'metier_id et metier_label requis'], 422);
                return;
            }

            (new DashboardModel())->recordMetierSearch($metierId, $metierLabel, $visitorId, $clientInfo);

            Response::json(['message' => 'Recherche enregistrée'], 201);
        } catch (Exception $e) {
            error_log("Erreur dans trackSearch: " . $e->getMessage());
            Response::json(['message' => 'Erreur serveur'], 500);
        }
    }
   
    public static function getTopMetiers(): void
    {
        try {
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            if ($limit < 1) $limit = 10;
            if ($limit > 100) $limit = 100;

            $model = new DashboardModel();
            
            $startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
            $endDate = date('Y-m-d') . ' 23:59:59';
            
            $topMetiers = $model->getTopMetiersRecherches($startDate, $endDate, $limit);

            Response::json(['metiers' => $topMetiers]);
        } catch (Exception $e) {
            error_log("Erreur dans getTopMetiers: " . $e->getMessage());
            Response::json(['message' => 'Erreur serveur'], 500);
        }
    }
   
    public static function trackEtablissementSelection(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $metierLabel = isset($body['metier_label']) ? trim($body['metier_label']) : null;
            $region = isset($body['region']) ? trim($body['region']) : null;
            $etablissementNom = isset($body['etablissement_nom']) ? trim($body['etablissement_nom']) : null;
            $visitorId = $body['visitor_id'] ?? null;
            $clientInfo = $body['client_info'] ?? null;

            if (!$metierLabel || !$region) {
                Response::json(['message' => 'metier_label et region requis'], 422);
                return;
            }

            (new DashboardModel())->recordEtablissementSelection($metierLabel, $region, $etablissementNom, $visitorId, $clientInfo);

            Response::json(['message' => 'Sélection d\'établissement enregistrée'], 201);
        } catch (Exception $e) {
            error_log("Erreur dans trackEtablissementSelection: " . $e->getMessage());
            Response::json(['message' => 'Erreur serveur'], 500);
        }
    }

    public static function index(): void
    {
        try {
            $period = $_GET['period'] ?? '30j';
            $start  = $_GET['start']  ?? null;
            $end    = $_GET['end']    ?? null;

            [$startDate, $endDate] = self::resolveDates($period, $start, $end);

            $model      = new DashboardModel();
            $totalViews = $model->getTotalViews($startDate, $endDate);
            $trendViews = $model->getTrendViews();
            $topMetiers = $model->getTopMetiersRecherches($startDate, $endDate);
            $topMetier  = $topMetiers[0] ?? ['name' => '—', 'value' => 0, 'croissance' => '0%'];

            $response = [
                'stats' => [
                    'total_views' => (int) $totalViews,
                    'trend_views' => (float) $trendViews,
                    'top_metier'  => [
                        'name' => $topMetier['name'],
                        'value' => (int) ($topMetier['value'] ?? 0),
                        'croissance' => (string) ($topMetier['croissance'] ?? '0%')
                    ],
                ],
                'charts' => [
                    'monthly_visibility' => $model->getMonthlyVisibilite(),
                    'weekly_activity'    => $model->getWeeklyActivity(),
                    'top_metiers'        => $model->formatTopMetiersForChart($topMetiers),
                    'views_by_page'      => $model->getViewsByPage($startDate, $endDate),
                ],
                'period' => [
                    'start' => $startDate,
                    'end'   => $endDate,
                ],
            ];

        
            error_log("Dashboard response - total_views: " . $totalViews . " (" . gettype($totalViews) . ")");

            Response::json($response);
        } catch (Exception $e) {
            error_log("Erreur dans dashboard index: " . $e->getMessage());
            Response::json(['message' => 'Erreur serveur'], 500);
        }
    }
   
    private static function resolveDates(string $period, ?string $start, ?string $end): array
    {      
        $endOfToday = date('Y-m-d') . ' 23:59:59';
        return match ($period) {
            'today'     => [date('Y-m-d') . ' 00:00:00', $endOfToday],
            '7j'        => [date('Y-m-d', strtotime('-7 days'))   . ' 00:00:00', $endOfToday],
            '30j'       => [date('Y-m-d', strtotime('-30 days'))  . ' 00:00:00', $endOfToday],
            'this_year' => [date('Y') . '-01-01 00:00:00', $endOfToday],
            '12m'       => [date('Y-m-d', strtotime('-12 months')) . ' 00:00:00', $endOfToday],
            'all'       => ['2000-01-01 00:00:00', $endOfToday],
            'custom'    => [
                ($start ?? date('Y-m-d', strtotime('-30 days'))) . ' 00:00:00',
                ($end   ?? date('Y-m-d'))                        . ' 23:59:59',
            ],
            default     => [date('Y-m-d', strtotime('-30 days')) . ' 00:00:00', $endOfToday],
        };
    }
}