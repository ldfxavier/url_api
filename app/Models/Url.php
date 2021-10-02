<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    use HasFactory;

    /**
     * Mount.
     *
     * @return array
     */
    public function mount($data)
    {
        $array = [
            'id' => $data->id,
            'url' =>  $data->url,
            'content' =>  $data->content,
            'status' => $data->status,
            'updated_at' =>  [
                'value' => $data->updated_at,
                'br' => $data->updated_at !== null ? date('d/m/Y H:i', strtotime($data->updated_at)) : '',
                'date' => $data->updated_at !== null ? date('d/m/Y', strtotime($data->updated_at)) : '',
                'hour' => $data->updated_at !== null ? date('H:i', strtotime($data->updated_at)) : '',
            ],
            'created_at' =>  [
                'value' => $data->created_at,
                'br' => $data->created_at !== null ? date('d/m/Y H:i', strtotime($data->created_at)) : '',
                'date' => $data->created_at !== null ? date('d/m/Y', strtotime($data->created_at)) : '',
                'hour' => $data->created_at !== null ? date('H:i', strtotime($data->created_at)) : '',
            ],
        ];

        return $array;
    }

    /**
     * List.
     *
     * @return array
     */
    public function index($request)
    {
        try {

            $data = $this->orderBy('id', 'DESC')->paginate($request->query('quantidade') ? $request->query('quantidade') : 15);

            if($data->isNotEmpty()){
                foreach($data as $r):
                    $array[] = $this->mount($r);
                endforeach;

                $proximaPagina = $data->currentPage() >= $data->lastPage() ? null : $data->currentPage() + 1;
                $paginaAnterior = $data->currentPage() > 1 ? $data->currentPage() - 1 : null;

                $array = [
                    'items' => $array,
                    'count' => $data->count(),
                    'current_page' => $data->currentPage(),
                    'next_page' => $proximaPagina,
                    'prev_page' => $paginaAnterior,
                    'last_page' => $data->lastPage(),
                    'total' => $data->total(),
                ];

                return $array;
            }

            return response([
                'error' => true,
                'message' => 'Nenhuma url encontrado!'
            ], 404);
        } catch (\Exception $e) {
            return response($e, 400);
        }
    }

    /**
     * Show.
     *
     * @return array
     */
    public function show($id)
    {
        try {
            $user = $this->find($id);

            if ($user) :
                $user->avatar = null !== $user->avatar ? asset('storage/' . $user->avatar) : null;
                return response($user);
            endif;
        } catch (\Exception $e) {
            return response([
                'error' => true,
                'message' => 'Você não tem permissão para acessar esse usuário!'
            ], 401);
        }
    }

    /**
     * Create.
     *
     * @return array
     */
    public function store($request)
    {
        try {
            if (empty($request->url)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "Url" não pode ser vazio!',
                ], 400);
            endif;

            if(!validateUrl($request->url)):
                return response([
                    'error' => true,
                    'message' => 'Url inválida! A url precisa ser completa (http(s)://url.com',
                ], 400);
            endif;

            $verify = $this->where('url', $request->url)->first();

            if($verify):
                return response([
                    'error' => true,
                    'message' => 'Url já cadastrada!',
                ], 400);
            endif;

            $this->url = $request->url;

            if ($this->save()) :
                return response([
                    'error' => false,
                    'message' => 'Url criada com sucesso!'
                ]);
            endif;
        } catch (\Exception $e) {
            return response($e, 400);
        }
    }

    /**
     * Update.
     *
     * @return array
     */
    public function updatedUrl($id, $request)
    {
        try {
            if (empty($request->url)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "Url" não pode ser vazio!',
                ], 400);
            endif;

            if(!validateUrl($request->url)):
                return response([
                    'error' => true,
                    'message' => 'Url inválida! A url precisa ser completa (http(s)://url.com',
                ], 400);
            endif;

            $update['url'] = $request->url;

            if (!empty($request->content)) :
                $update['content'] = $request->content;
            endif;

            if (!empty($request->status)) :
                $update['status'] = $request->status;
            endif;

            if ($this->where('id', $id)->update($update)) :
                return response([
                    'error' => false,
                    'message' => 'Dados atualizados com sucesso!',
                ]);
            else :
                return response([
                    'error' => false,
                    'message' => 'Nenhum dado atualizado!',
                ]);

            endif;
        } catch (\Exception $e) {
            return response($e, 400);
        }
    }

    /**
     * Get content url.
     *
     * @return array
     */
    public function content()
    {
        try {
            $data = $this->get();
            if($data->isNotEmpty()):
                foreach($data as $r):
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL            => $r->url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_COOKIEJAR      => 'cookies.txt',
                        CURLOPT_SSL_VERIFYPEER => false,
                    ]);
                    $response = curl_exec($ch);
                    $error = curl_error($ch);
                    $info = curl_getinfo($ch);
                    curl_close($ch);

                    if(empty($error)):
                        $update['content'] = html_entity_decode($response);
                    endif;

                    $update['status'] = $info['http_code'];
                    $this->where('url', $r->url)->update($update);
                endforeach;
            endif;
        } catch (\Exception $e) {
            return response($e, 400);
        }
    }
}
