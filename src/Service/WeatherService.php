<?php

namespace App\Service;

use App\Entity\LocationWeather;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private const WEATHER_API_URL = 'https://api.openweathermap.org/data/2.5/weather';
    private const WEATHER_ICON_URL = 'https://openweathermap.org/img/wn/';
    
    private ?string $apiKey;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    
    public function __construct(
        ParameterBagInterface $params,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->apiKey = $params->has('app.openweather_api_key') ? $params->get('app.openweather_api_key') : null;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }
    
    /**
     * Fetch current weather for a location by coordinates
     */
    public function getWeatherForLocation(float $latitude, float $longitude): array
    {
        if (!$this->apiKey) {
            $this->logger->warning('OpenWeatherMap API key is not configured');
            return $this->getDefaultWeatherData();
        }
        
        try {
            $response = $this->httpClient->request('GET', self::WEATHER_API_URL, [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ]
            ]);
            
            $data = $response->toArray();
            
            return [
                'success' => true,
                'temperature' => $data['main']['temp'],
                'condition' => $this->mapWeatherCondition($data['weather'][0]['main']),
                'icon' => self::WEATHER_ICON_URL . $data['weather'][0]['icon'] . '.png',
                'description' => $data['weather'][0]['description'],
                'wind' => $data['wind']['speed'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'pressure' => $data['main']['pressure'] ?? null,
                'raw_data' => $data
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching weather data: ' . $e->getMessage());
            return array_merge(
                $this->getDefaultWeatherData(),
                ['error' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Update LocationWeather entity with fresh weather data
     */
    public function updateLocationWeather(LocationWeather $location): LocationWeather
    {
        $weatherData = $this->getWeatherForLocation(
            $location->getLatitude(),
            $location->getLongitude()
        );
        
        if ($weatherData['success']) {
            $location->setTemperature($weatherData['temperature']);
            $location->setWeatherCondition($weatherData['condition']);
            $location->setUpdatedAt(new \DateTime());
        }
        
        return $location;
    }
    
    /**
     * Map OpenWeatherMap condition to our app condition
     */
    private function mapWeatherCondition(string $apiCondition): string
    {
        return match (strtolower($apiCondition)) {
            'clear' => 'Sunny',
            'clouds' => 'Cloudy',
            'rain', 'drizzle' => 'Rainy',
            'snow' => 'Snowy',
            'thunderstorm' => 'Stormy',
            'mist', 'fog', 'haze' => 'Foggy',
            default => 'Cloudy'
        };
    }
    
    /**
     * Get default weather data when API is unavailable
     */
    private function getDefaultWeatherData(): array
    {
        return [
            'success' => false,
            'temperature' => 20.0,
            'condition' => 'Cloudy',
            'icon' => null,
            'description' => 'Weather data unavailable',
            'wind' => null,
            'humidity' => null,
            'pressure' => null
        ];
    }
} 