<?php

namespace NFService\Piperun;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API Piperun
 *
 * @category  NFService
 * @package   NFService\Piperun\Tools
 * @author    Henrique Ernandes Rebelo <rebeloehenrique at hotmail dot com>
 * @copyright 2023 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * URL base para comunicação com a API
     *
     * @var string
     */
    public static $API_URL = 'https://api.pipe.run/v1';

    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, ambiente(produção ou homologação) e debug(true|false)
     *
     * @var array
     */
    private $config = [
        'token' => '',
        'debug' => false,
        'upload' => false,
        'decode' => true
    ];

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) :void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Função responsável por definir o token a ser utilizado para comunicação com a API
     *
     * @param string $token Token para autenticação na API
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token'] = $token;
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Retorna o token utilizado para comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getToken() :string
    {
        return $this->config['token'];
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'Token: '.$this->config['token'],
            'Accept: application/json',
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }
        return $headers;
    }

    /**
     * Consulta os funis
     *
     * @access public
     * @return array
     */
    public function consultaFunis(array $params = []): array
    {
        try {
            $dados = $this->get("pipelines", $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Consulta se a pessoa existe
     *
     * @access public
     * @return array
     */
    public function consultaPessoaTelefone(string $telefone, array $params = []): array
    {
        if(!isset($telefone) || empty($telefone)) {
            throw new Exception('Telefone inválido', 1);
        }

        try {
            $dados = $this->get("persons?with=deals&phone=$telefone", $params);

            if ($dados['body']->success) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Consulta se a pessoa existe
     *
     * @access public
     * @return array
     */
    public function consultaPessoaEmail(string $email, array $params = []): array
    {
        $params = array_filter($params, function($item) {
                return $item['name'] !== 'email';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'email',
                'value' => $email
            ];

        try {
            $dados = $this->get("persons", $params);

            if ($dados['body']->success) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }


     /**
     * Cadastra uma pessoa
     *
     * @param array $dados Dados da pessoa
     *
     * @access public
     * @return array
     */
    public function cadastraPessoa(array $dados, array $params = []): array
    {
        $errors = [];
        if (!isset($dados['name']) || empty($dados['name'])) {
            $errors[] = 'É obrigatório o nome do contato';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->post("persons", $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Atualiza uma pessoa
     *
     * @param int $pessoa_id Id da pessoa
     * @param array $dados Dados da pessoa
     *
     * @access public
     * @return array
     */
    public function atualizaPessoa(int $pessoa_id, array $dados, array $params = []): array
    {

        if (!isset($pessoa_id) || empty($pessoa_id)) {
            $errors[] = 'É obrigatório o id da pessoa a ser atualizada';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->put("persons/" . $pessoa_id, $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Remove uma pessoa
     *
     * @param int $pessoa_id Id da pessoa
     * @param array $dados Dados da pessoa
     *
     * @access public
     * @return array
     */
    public function removePessoa(int $pessoa_id, array $params = []): array
    {

        if (!isset($pessoa_id) || empty($pessoa_id)) {
            $errors[] = 'É obrigatório o id da pessoa a ser removida';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->delete("persons/" . $pessoa_id, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Adiciona nota a uma pessoa
     * @param int $pessoa_id Id da pessoa
     * @param int $deal_id Id da oportunidade
     * @param string $text Texto da nota
     */
    public function cadastraNotaPessoaOportunidade(int $pessoa_id, int $deal_id, string $text, array $params = []): array
    {
        $errors = [];
        if (!isset($pessoa_id) || empty($pessoa_id)) {
            $errors[] = 'É obrigatório o id da pessoa';
        }
        if (!isset($deal_id) || empty($deal_id)) {
            $errors[] = 'É obrigatório o id da oportunidade';
        }
        if (!isset($text) || empty($text)) {
            $errors[] = 'É obrigatório o texto da nota';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        $dados = [
            'text' => $text,
            'person_id' => $pessoa_id,
            'deal_id' => $deal_id
        ];

        try {
            $dados = $this->post("notes", $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Remove nota de uma pessoa
     * @param int $pessoa_id Id da pessoa
     * @param string $text Texto da nota
     */
    public function removeNotaPessoa(int $nota_id, array $params = []): array
    {
        $errors = [];
        if (!isset($nota_id) || empty($nota_id)) {
            $errors[] = 'É obrigatório o id da nota';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->delete("notes/$nota_id", $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Adiciona nota a uma empresa
     * @param int $company_id Id da empresa
     * @param int $deal_id Id da oportunidade
     * @param string $text Texto da nota
     */
    public function cadastraNotaEmpresaOportunidade(int $company_id, int $deal_id, string $text, array $params = []): array
    {
        $errors = [];
        if (!isset($company_id) || empty($company_id)) {
            $errors[] = 'É obrigatório o id da empresa';
        }
        if (!isset($deal_id) || empty($deal_id)) {
            $errors[] = 'É obrigatório o id da oportunidade';
        }
        if (!isset($text) || empty($text)) {
            $errors[] = 'É obrigatório o texto da nota';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        $dados = [
            'text' => $text,
            'company_id' => $company_id,
            'deal_id' => $deal_id
        ];

        try {
            $dados = $this->post("notes", $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Consulta se a empresa existe
     *
     * @access public
     * @return array
     */
    public function consultaEmpresaCnpj(string $cnpj, array $params = []): array
    {
        $params = array_filter($params, function($item) {
                return $item['name'] !== 'cnpj';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'cnpj',
                'value' => $cnpj
            ];

        try {
            $dados = $this->get("companies", $params);

            if ($dados['body']->success) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Consulta se a empresa existe com o telefone
     *
     * @access public
     * @return array
     */
    public function consultaEmpresaTelefone(string $telefone, array $params = []): array
    {
        if(!isset($telefone) || empty($telefone)) {
            throw new Exception('Telefone inválido', 1);
        }

        try {
            $dados = $this->get("companies?with=deals&phone=$telefone", $params);

            if ($dados['body']->success) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra uma Empresa
     *
     * @param array $dados Dados da Empresa
     *
     * @access public
     * @return array
     */
    public function cadastraEmpresa(array $dados, array $params = []): array
    {

        $errors = [];
        if (!isset($dados['name']) || empty($dados['name'])) {
            $errors[] = 'É obrigatório o nome da Empresa';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->post("companies", $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Atualiza uma Empresa
     *
     * @param int $empresa_id Id da Empresa no PipeRun
     * @param array $dados Dados da Empresa
     *
     * @access public
     * @return array
     */
    public function atualizaEmpresa(int $empresa_id, array $dados, array $params = []): array
    {

        if (!isset($empresa_id) || empty($empresa_id)) {
            $errors[] = 'É obrigatório o id da Empresa a ser atualizada';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->put("companies/" . $empresa_id, $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Remove uma Empresa
     *
     * @param int $empresa_id Id da Empresa
     * @param array $dados Dados da Empresa
     *
     * @access public
     * @return array
     */
    public function removeEmpresa(int $empresa_id, array $params = []): array
    {

        if (!isset($empresa_id) || empty($empresa_id)) {
            $errors[] = 'É obrigatório o id da pessoa a ser removida';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->delete("companies/" . $empresa_id, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Consulta o id da cidade
     *
     * @access public
     * @return array
     */
    public function consultaCidadeId(string $cidade, $uf, array $params = []): array
    {
        $params = array_filter($params, function($item) {
                return $item['name'] !== 'cidade';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'name',
                'value' => $cidade
            ];
            $params[] = [
                'name' => 'uf',
                'value' => $uf
            ];

        try {
            $dados = $this->get("cities", $params);

            if ($dados['body']->success) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Consulta oportunidades pelo conta_id
     *
     * @access public
     * @return array
     */
    public function consultaOportunidade(int $conta_id, array $params = []): array
    {
        $params = array_filter($params, function($item) {
                return $item['name'] !== 'conta_id';
            }, ARRAY_FILTER_USE_BOTH);
            $params[] = [
                'name' => 'custom_fields[427888]', //custom field criado no piperun
                'value' => "'$conta_id'"
            ];
            $params[] = [
                'name' => 'with', //custom field criado no piperun
                'value' => 'customFields'
            ];

        try {
            $dados = $this->get("deals", $params);

            if ($dados['body']->success) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra uma oportunidade
     *
     * @param array $dados Dados da oportunidade
     *
     * @access public
     * @return array
     */
    public function cadastraOportunidade(array $dados, array $params = []): array
    {
        $errors = [];
        if (!isset($dados['pipeline_id']) || empty($dados['pipeline_id'])) {
            $errors[] = 'É obrigatório o ID do funil que será cadastrado a oportunidade';
        }
        if (!isset($dados['stage_id']) || empty($dados['stage_id'])) {
            $errors[] = 'É obrigatório o ID da etapa que será cadastrado a oportunidade';
        }
        if (!isset($dados['title']) || empty($dados['title'])) {
            $errors[] = 'É obrigatório o Título da oportunidade';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->post("deals", $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Atualiza uma oportunidade
     *
     * @param int $oportunidade_id Id da oportunidade
     * @param array $dados Dados da oportunidade
     *
     * @access public
     * @return array
     */
    public function atualizaOportunidade(int $oportunidade_id, array $dados, array $params = []): array
    {

        if (!isset($oportunidade_id) || empty($oportunidade_id)) {
            $errors[] = 'É obrigatório o id da oportunidade a ser atualizada';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->put("deals/" . $oportunidade_id, $dados, $params);

            if ($dados['body']->success) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Remove uma oportunidade
     *
     * @param int $oportunidade_id Id da oportunidade
     * @param array $dados Dados da oportunidade
     *
     * @access public
     * @return array
     */
    public function removeOportunidade(int $oportunidade_id, array $params = []): array
    {

        if (!isset($oportunidade_id) || empty($oportunidade_id)) {
            $errors[] = 'É obrigatório o id da oportunidade a ser removida';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->delete("deals/" . $oportunidade_id, $params);

            if ($response['httpCode'] == 204) {
                return $response;
            }

            if (isset($response['body']->message)) {
                throw new Exception($response['body']->message, 1);
            }

            throw new Exception(json_encode($response), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }


    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? json_encode($body) : $body,
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PATCH Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function patch(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access protected
     * @return array
     */
    protected function execute(string $path, array $opts = [], array $params = []) :array
    {
        $params = array_filter($params, function($item) {
            return $item['name'] !== 'token';
        }, ARRAY_FILTER_USE_BOTH);

        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = self::$API_URL.$path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $param) {
                if (isset($param['name']) && !empty($param['name']) && isset($param['value']) && !empty($param['value'])) {
                    $paramsJoined[] = urlencode($param['name'])."=".urlencode($param['value']);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }
}