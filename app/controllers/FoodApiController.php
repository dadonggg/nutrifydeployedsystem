<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;

/**
 * USDA FoodData Central API Proxy
 * Keeps API key secure on server-side.
 * Falls back to a built-in food database if the USDA API is unreachable.
 */
final class FoodApiController extends Controller
{
    private const API_BASE_URL = 'https://api.nal.usda.gov/fdc/v1';

    /**
     * Built-in fallback food list (nutrients per 100 g).
     * Nutrient IDs match USDA FoodData Central format so the JS can process them.
     */
    private const LOCAL_FOODS = [
        ['fdcId' => 1, 'description' => 'Chicken Breast, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 165], // calories
             ['nutrientId' => 1003, 'value' => 31.0], // protein
             ['nutrientId' => 1005, 'value' => 0.0],  // carbs
             ['nutrientId' => 1004, 'value' => 3.6],  // fat
         ]],
        ['fdcId' => 2, 'description' => 'Brown Rice, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 123],
             ['nutrientId' => 1003, 'value' => 2.7],
             ['nutrientId' => 1005, 'value' => 25.6],
             ['nutrientId' => 1004, 'value' => 0.9],
         ]],
        ['fdcId' => 3, 'description' => 'White Rice, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 130],
             ['nutrientId' => 1003, 'value' => 2.7],
             ['nutrientId' => 1005, 'value' => 28.2],
             ['nutrientId' => 1004, 'value' => 0.3],
         ]],
        ['fdcId' => 4, 'description' => 'Egg, whole, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 143],
             ['nutrientId' => 1003, 'value' => 12.6],
             ['nutrientId' => 1005, 'value' => 0.7],
             ['nutrientId' => 1004, 'value' => 9.5],
         ]],
        ['fdcId' => 5, 'description' => 'Banana, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 89],
             ['nutrientId' => 1003, 'value' => 1.1],
             ['nutrientId' => 1005, 'value' => 23.0],
             ['nutrientId' => 1004, 'value' => 0.3],
         ]],
        ['fdcId' => 6, 'description' => 'Oats, dry', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 389],
             ['nutrientId' => 1003, 'value' => 16.9],
             ['nutrientId' => 1005, 'value' => 66.3],
             ['nutrientId' => 1004, 'value' => 6.9],
         ]],
        ['fdcId' => 7, 'description' => 'Salmon, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 208],
             ['nutrientId' => 1003, 'value' => 20.4],
             ['nutrientId' => 1005, 'value' => 0.0],
             ['nutrientId' => 1004, 'value' => 13.4],
         ]],
        ['fdcId' => 8, 'description' => 'Sweet Potato, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 90],
             ['nutrientId' => 1003, 'value' => 2.0],
             ['nutrientId' => 1005, 'value' => 20.7],
             ['nutrientId' => 1004, 'value' => 0.1],
         ]],
        ['fdcId' => 9, 'description' => 'Broccoli, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 35],
             ['nutrientId' => 1003, 'value' => 2.4],
             ['nutrientId' => 1005, 'value' => 7.2],
             ['nutrientId' => 1004, 'value' => 0.4],
         ]],
        ['fdcId' => 10, 'description' => 'Milk, whole', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 61],
             ['nutrientId' => 1003, 'value' => 3.2],
             ['nutrientId' => 1005, 'value' => 4.8],
             ['nutrientId' => 1004, 'value' => 3.3],
         ]],
        ['fdcId' => 11, 'description' => 'Greek Yogurt, plain', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 59],
             ['nutrientId' => 1003, 'value' => 10.0],
             ['nutrientId' => 1005, 'value' => 3.6],
             ['nutrientId' => 1004, 'value' => 0.4],
         ]],
        ['fdcId' => 12, 'description' => 'Almonds, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 579],
             ['nutrientId' => 1003, 'value' => 21.2],
             ['nutrientId' => 1005, 'value' => 21.6],
             ['nutrientId' => 1004, 'value' => 49.9],
         ]],
        ['fdcId' => 13, 'description' => 'Peanut Butter', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 588],
             ['nutrientId' => 1003, 'value' => 25.0],
             ['nutrientId' => 1005, 'value' => 20.0],
             ['nutrientId' => 1004, 'value' => 50.0],
         ]],
        ['fdcId' => 14, 'description' => 'Apple, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 52],
             ['nutrientId' => 1003, 'value' => 0.3],
             ['nutrientId' => 1005, 'value' => 13.8],
             ['nutrientId' => 1004, 'value' => 0.2],
         ]],
        ['fdcId' => 15, 'description' => 'Tuna, canned in water', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 116],
             ['nutrientId' => 1003, 'value' => 25.5],
             ['nutrientId' => 1005, 'value' => 0.0],
             ['nutrientId' => 1004, 'value' => 1.0],
         ]],
        ['fdcId' => 16, 'description' => 'Bread, whole wheat', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 247],
             ['nutrientId' => 1003, 'value' => 13.0],
             ['nutrientId' => 1005, 'value' => 41.3],
             ['nutrientId' => 1004, 'value' => 3.4],
         ]],
        ['fdcId' => 17, 'description' => 'Pasta, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 158],
             ['nutrientId' => 1003, 'value' => 5.8],
             ['nutrientId' => 1005, 'value' => 30.9],
             ['nutrientId' => 1004, 'value' => 0.9],
         ]],
        ['fdcId' => 18, 'description' => 'Beef, ground, 90% lean, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 218],
             ['nutrientId' => 1003, 'value' => 26.1],
             ['nutrientId' => 1005, 'value' => 0.0],
             ['nutrientId' => 1004, 'value' => 12.3],
         ]],
        ['fdcId' => 19, 'description' => 'Orange, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 47],
             ['nutrientId' => 1003, 'value' => 0.9],
             ['nutrientId' => 1005, 'value' => 11.8],
             ['nutrientId' => 1004, 'value' => 0.1],
         ]],
        ['fdcId' => 20, 'description' => 'Tofu, firm', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 76],
             ['nutrientId' => 1003, 'value' => 8.1],
             ['nutrientId' => 1005, 'value' => 1.9],
             ['nutrientId' => 1004, 'value' => 4.2],
         ]],
        ['fdcId' => 21, 'description' => 'Avocado, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 160],
             ['nutrientId' => 1003, 'value' => 2.0],
             ['nutrientId' => 1005, 'value' => 8.5],
             ['nutrientId' => 1004, 'value' => 14.7],
         ]],
        ['fdcId' => 22, 'description' => 'Pork Chop, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 242],
             ['nutrientId' => 1003, 'value' => 27.3],
             ['nutrientId' => 1005, 'value' => 0.0],
             ['nutrientId' => 1004, 'value' => 14.0],
         ]],
        ['fdcId' => 23, 'description' => 'Spinach, raw', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 23],
             ['nutrientId' => 1003, 'value' => 2.9],
             ['nutrientId' => 1005, 'value' => 3.6],
             ['nutrientId' => 1004, 'value' => 0.4],
         ]],
        ['fdcId' => 24, 'description' => 'Cottage Cheese, low-fat', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 72],
             ['nutrientId' => 1003, 'value' => 12.4],
             ['nutrientId' => 1005, 'value' => 2.7],
             ['nutrientId' => 1004, 'value' => 1.0],
         ]],
        ['fdcId' => 25, 'description' => 'Lentils, cooked', 'brandName' => 'Generic',
         'foodNutrients' => [
             ['nutrientId' => 1008, 'value' => 116],
             ['nutrientId' => 1003, 'value' => 9.0],
             ['nutrientId' => 1005, 'value' => 20.1],
             ['nutrientId' => 1004, 'value' => 0.4],
         ]],
    ];

    /**
     * Helper to perform a USDA API request with automatic fallback to DEMO_KEY if the primary key is invalid/blocked.
     */
    private function requestUsda(string $endpoint, array $params): ?string
    {
        $config = \App\Core\Container::get('config');
        $primaryKey = (string)($config['usda']['api_key'] ?? 'DEMO_KEY');
        $fallbackKey = 'DEMO_KEY';

        // 1. Try with primary key
        $params['api_key'] = !empty($primaryKey) ? $primaryKey : $fallbackKey;
        $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            // If primary key failed with 403 / API key error, retry with fallback
            if (is_array($data) && isset($data['error'])) {
                $params['api_key'] = $fallbackKey;
                $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
                $fallbackResponse = @file_get_contents($url, false, $context);
                if ($fallbackResponse !== false) {
                    return $fallbackResponse;
                }
            }
            return $response;
        }

        // 2. If primary key request completely failed to open stream, try with fallback
        $params['api_key'] = $fallbackKey;
        $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
        $fallbackResponse = @file_get_contents($url, false, $context);
        return $fallbackResponse ?: null;
    }

    /**
     * Search the built-in food list by keyword (case-insensitive substring match).
     * Returns data in the same shape as the USDA API response.
     */
    private function searchLocalFoods(string $query): array
    {
        $queryLower = strtolower($query);
        $results = [];
        foreach (self::LOCAL_FOODS as $food) {
            if (strpos(strtolower($food['description']), $queryLower) !== false) {
                $results[] = $food;
            }
        }
        return ['foods' => $results, 'totalHits' => count($results), 'source' => 'local'];
    }

    /**
     * Search for foods
     * GET: index.php?r=foodApi/search&query=egg
     */
    public function searchAction(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $query = trim($_GET['query'] ?? '');
        
        if (empty($query)) {
            echo json_encode(['error' => 'Query parameter is required']);
            return;
        }

        if (strlen($query) < 2) {
            echo json_encode(['foods' => []]);
            return;
        }

        try {
            $response = $this->requestUsda('foods/search', [
                'query' => $query,
                'pageSize' => 20
            ]);
            
            if ($response === null) {
                // USDA API unreachable — use local fallback
                echo json_encode($this->searchLocalFoods($query));
                return;
            }

            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Malformed response — use local fallback
                echo json_encode($this->searchLocalFoods($query));
                return;
            }

            // If API returned an error (e.g., rate limit), use local fallback
            if (isset($data['error'])) {
                echo json_encode($this->searchLocalFoods($query));
                return;
            }

            // Return the real API response
            echo json_encode($data);

        } catch (\Exception $e) {
            // On any exception, fall back to local data
            echo json_encode($this->searchLocalFoods($query));
        }
    }

    /**
     * Get detailed food information by FDC ID
     * GET: index.php?r=foodApi/details&fdcId=123456
     */
    public function detailsAction(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $fdcId = trim($_GET['fdcId'] ?? '');
        
        if (empty($fdcId) || !is_numeric($fdcId)) {
            echo json_encode(['error' => 'Valid fdcId parameter is required']);
            return;
        }

        try {
            $response = $this->requestUsda('food/' . $fdcId, []);
            
            if ($response === null) {
                // Try local foods as fallback
                $fdcIdInt = (int)$fdcId;
                foreach (self::LOCAL_FOODS as $food) {
                    if ($food['fdcId'] === $fdcIdInt) {
                        echo json_encode($food);
                        return;
                    }
                }
                echo json_encode(['error' => 'Food not found']);
                return;
            }

            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['error' => 'Invalid JSON response from API']);
                return;
            }

            echo json_encode($data);

        } catch (\Exception $e) {
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
