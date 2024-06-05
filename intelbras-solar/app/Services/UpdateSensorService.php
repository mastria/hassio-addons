<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class UpdateSensorService
{
    private $API_URL = null;
    private $TOKEN = null;
    private $CLIENT = null;

    public function __construct()
    {
        $this->API_URL = env('SUPERVISOR_URL', 'http://supervisor/core/api/');
        $this->TOKEN = env('SUPERVISOR_TOKEN');
        $this->CLIENT = new Client([
            'base_uri' => $this->API_URL,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->TOKEN,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function status(string $status)
    {
        $_status = [
            '1' => 'Normal',
            '-1' => 'Desconectado',
            '3' => 'Falha',
            '4' => 'Desligado',
        ];

        return $_status[$status] ?? 'Desconhecido';
    }

    public function update(array $estacao): void
    {
        $data = [
            'state' => $estacao['eToday'],
            'attributes' => [
                'friendly_name' => Str::upper($estacao['alias']),
                'unit_of_measurement' => 'kWh',
                'icon' => 'mdi:solar-power-variant-outline',
                'device_class' => 'energy',
                'state_class' => 'measurement',
            ],
            'unique_id' => 'intelbras_' . $estacao['sn'],
        ];

        $estacao['status'] = $this->status($estacao['status']);
        $data['attributes'] = array_merge($data['attributes'], $estacao);

        try {
            $response = $this->CLIENT->post('states/sensor.intelbras_' . $estacao['sn'], [
                'json' => $data,
            ]);

            if ($response->getStatusCode() === 200) {
                echo 'Sensor ' . $estacao['alias'] . ' atualizado com sucesso!' . PHP_EOL;
            }
        } catch (\Exception $e) {
            echo 'Erro ao atualizar sensor ' . $estacao['alias'] . ': ' . $e->getMessage() . PHP_EOL;
        }
    }
}
