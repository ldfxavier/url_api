<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Mount.
     *
     * @return array
     */
    public function mount($data)
    {
        $array = [
            'id' => $data->id,
            'name' =>  $data->name,
            'email' =>  $data->email,
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

            $data = $this->paginate($request->query('quantidade') ? $request->query('quantidade') : 15);

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
                'message' => 'Nenhum usuário encontrado!'
            ], 404);
        } catch (\Exception $e) {
            return response($e, 400);
        }
    }

    /**
     * Create.
     *
     * @return array
     */
    public function store($data)
    {
        try {
            if (empty($data->name)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "Nome" não pode ser vazio!',
                ], 400);
            else :
                $this->name = $data->name;
            endif;

            if (empty($data->email)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "E-mail" não pode ser vazio!',
                ], 400);
            else :
                $this->email = $data->email;
            endif;

            if (empty($data->password)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "Password" não pode ser vazio!',
                ], 400);
            endif;

            $this->password = Hash::make($data->password);
            if ($this->save()) :

                $credentials = ['email' => $data->email, 'password' => $data->password];

                if (!$token = auth('api')->attempt($credentials)) {
                    return response(
                        [
                            'error' => true,
                            'message' => 'Login ou senha incorreto!',
                        ],
                        401
                    );
                }

                $User = auth('api')->user();

                return response([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                    'dados' => $this->mount($User),
                    'message' => 'Usuário criado com sucesso!'
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
    public function updatedUser($request)
    {
        try {
            if (empty($request->name)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "Nome" não pode ser vazio!',
                ], 400);
            else :
                $update['name'] = $request->name;
            endif;

            if (empty($request->email)) :
                return response([
                    'error' => true,
                    'message' => 'O campo "E-mail" não pode ser vazio!',
                ], 400);
            else :
                $update['email'] = $request->email;
            endif;

            $id = auth('api')->user()->id;

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
     * Me.
     *
     * @return array
     */
    public function me()
    {
        try {
            $user = auth('api')->user();

            if ($user) :
                $user->avatar = null !== $user->avatar ? asset('storage/' . $user->avatar) : null;
                $user->data_nascimento =  date('d/m/Y', strtotime($user->data_nascimento));
                $user->cpf = str_pad($user->cpf, 11, '0', STR_PAD_LEFT);
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
     * Update password.
     *
     * @return array
     */
    public function password($data)
    {
        try {

            $user = auth('api')->user();

            if (!Hash::check($data->password, $user->password)) {
                return response([
                    'error' => true,
                    'message' => 'A senha digitada está incorreta!',
                ], 401);
            } else if ($data->password_new !== $data->password_confirm) {
                return response([
                    'error' => true,
                    'message' => 'As senhas digitadas não são iguais!',
                ], 401);
            }
            $update = [
                'password' => Hash::make($data->password_new),
            ];

            if ($this->where('id', $user->id)->update($update)) :
                return response([
                    'error' => false,
                    'message' => 'Senha atualizada com sucesso!',
                ]);
            endif;
        } catch (\Exception $e) {
            return response(['message' => 'Você não tem permissão para acessar essa rota!'], 401);
        }
    }
}
