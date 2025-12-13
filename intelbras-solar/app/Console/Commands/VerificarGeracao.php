<?php

namespace App\Console\Commands;

use App\Services\UpdateSensorService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class VerificarGeracao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intelbras:verificar-geracao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar a quantidade de energia gerada por estação';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function __construct(protected UpdateSensorService $updateSensorService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client(array(
            'cookies' => true
        ));

        $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/login', [
            'timeout' => 30,
            'form_params' => [
                'account' => config('intelbras.user'),
                'password' => config('intelbras.password'),
                'validateCode' => '',
                'lang' => 'en'
            ]
        ]);

        // Plantas
        $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/index/getPlantListTitle');

        $plantas = json_decode($response->getBody(), true);

        if (!is_array($plantas) || count($plantas) == 0) {
            throw new Exception('Erro ao verificar as plantas');
        }

        // Inversores
        foreach ($plantas as $planta) {
            $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/panel/getDevicesByPlantList', [
                'form_params' => [
                    'plantId' => $planta['id'],
                    'currPage' => '1',
                ]
            ]);

            $retorno = json_decode($response->getBody(), true);

            if (isset($retorno['result']) && $retorno['result'] == '1') {
                foreach ($retorno['obj']['datas'] as $estacao) {
                    $this->updateSensorService->update($estacao);
                }
            } else {
                throw new Exception('Erro ao verificar a energia da planta ' . $planta['name']);
            }
        }
    }
}
