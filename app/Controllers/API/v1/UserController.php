<?php
/**
 * Created by PhpStorm.
 * User: Z3205
 * Date: 2019/5/8
 * Time: 12:30
 */
namespace App\Controllers\API\v1;

use App\Models\User;
use App\Middleware\API\v1\JwtToken as AuthService;
use App\Services\Config;
use App\Controllers\LinkController;
use App\Utils\Tools;
use App\Utils\URL;
use App\Utils\Hash;

class UserController
{
    public function info($request, $response, $args) {
        $token = explode(' ', $request->getHeaderLine('Authorization'));
        $token = isset($token[1]) ? $token[1] : '';
        $user = AuthService::getUser($token);


        $ssrSubLink = Config::get('subUrl') . LinkController::GenerateSSRSubCode($user->id, 0);

        $res['code'] = 0;
        $res['data'] = array(
            'id' => $user->id,
            'isAdmin' => $user->isAdmin(),
            'username' => $user->user_name,
            'trafficRemain' => $user->unusedTraffic(),
            'trafficTotal' => $user->enableTrafficInGB(),
            'balance' => $user->money,
            'accountExp' => strtotime($user->expire_in),
            'levelName' => 'Level. ' . $user->class,
            'ssrSub' => $ssrSubLink,
            'method' => $user->method,
            'obfs' => $user->obfs,
            'obfs_param' => $user->obfs_param,
            'port' => $user->port,
            'protocol' => $user->protocol,
            'protocol_param' => $user->protocol_param,
        );

        return $response->getBody()->write(json_encode($res));
    }

    public function getNodeConfig($request, $response, $args) {
        $token = explode(' ', $request->getHeaderLine('Authorization'));
        $token = isset($token[1]) ? $token[1] : '';
        $user = AuthService::getUser($token);

        $ssrEnable = 1;
        $ssrEnable = URL::SSRCanConnect($user);

        if ($ssrEnable == 1) {
            $muUsers = User::where("is_multi_user", "<>", 0)->get();
            if ($muUsers != Null) {
                $i = 0;
                $muInfo = array();
                foreach ($muUsers as $muUser) {
                    $muInfo[$i] = array(
                        'server_port' => $muUser->port,
                        'method' => $muUser->method,
                        'password' => $muUser->passwd,
                        'protocol' => $muUser->protocol,
                        'protocol_param' => $user->id.":".$user->passwd,
                        'obfs' => $muUser->obfs,
                        'obfs_param' => $user->getMuMd5(),
                        'ssr' => true
                    );

                    $i++; // Warning: the value will be a correct number after done. The client should use '$muInfo[ 0 ~ $i-1]' as condition.
                }
            }
        }

        $userInfo = array(
            'server_port' => $user->port,
	        'method' => $user->method,
	        'password' => $user->passwd,
	        'protocol' => $user->protocol,
	        'protocol_param' => $user->protocol_param,
	        'obfs' => $user->obfs,
	        'obfs_param' => $user->obfs_param,
            'ssr' => (bool)$ssrEnable
        );
        
        $ret = array(
            'muInfo' => $muInfo,
            'muCount' => $i,
            'userInfo' => $userInfo
        );

        return $response->getBody()->write(json_encode($ret));
    }

    public function updateInfo($request, $response, $args){
        $token = explode(' ', $request->getHeaderLine('Authorization'));
        $token = isset($token[1]) ? $token[1] : '';
        $user = AuthService::getUser($token);

        $oldPassword = $request->getParam('oldPass');
        $newPassword = $request->getParam('newPass');
        $newPasswordRep = $request->getParam('newPassRep');

        $obfs = $request->getParam('obfs');
        $obfs_param = $request->getParam('obfs_param');
        $protocol = $request->getParam('protocol');
        $protocol_param = $request->getParam('protocol_param');
        $method = $request->getParam('method');

        if($obfs && $protocol && $method){

            if (
                !Tools::is_param_validate('method', $method) ||
                !Tools::is_param_validate('protocol', $protocol) ||
                !Tools::is_param_validate('obfs', $obfs)
            ) {
                $res['code'] = -1;
                $res['msg'] = "参数不合法";
                return $response->getBody()->write(json_encode($res));
            }


            $user->method = $method;
            $user->obfs = $obfs;
            $user->obfs_param = $obfs_param;
            $user->protocol = $protocol;
            $user->protocol_param = $protocol_param;

            if (!URL::SSCanConnect($user) && !URL::SSRCanConnect($user)) {
                $res['code'] = -1;
                $res['msg'] = "配置不合法";
                return $response->getBody()->write(json_encode($res));
            }

            $user->save();


            $res['code'] = 0;
            $res['msg'] = "设置成功";
            return $response->getBody()->write(json_encode($res));

        }

        if($newPassword && $oldPassword && $newPasswordRep){
            if($newPassword != $newPasswordRep){
                $res['code'] = -1;
                $res['msg'] = "两次密码重复不一样";
                return $response->getBody()->write(json_encode($res));
            }

            if (!Hash::checkPassword($user->pass, $oldPassword)) {
                $res['code'] = -1;
                $res['msg'] = '旧密码错误';
                return $response->getBody()->write(json_encode($res));
            }


            if (strlen($newPassword) < 8) {
                $res['code'] = -1;
                $res['msg'] = '密码长度需大于 8 位';
                return $response->getBody()->write(json_encode($res));
            }

            $hashPwd = Hash::passwordHash($newPassword);
            $user->pass = $hashPwd;
            $user->save();

            $user->clean_link();

            $res['code'] = 0;
            $res['msg'] = '修改成功';
            return $response->getBody()->write(json_encode($res));
        }
        $res['code'] = -1;
        $res['msg'] = '无效参数';
        return $response->getBody()->write(json_encode($res));

    }

    public function getMethods($request, $response, $args){
        $res['code'] = 0;
        $res['data'] = array(
            'obfs' => Config::getSupportParam('obfs'),
            'protocol' => Config::getSupportParam('protocol'),
            'method' => Config::getSupportParam('method'),

        );
        return $response->getBody()->write(json_encode($res));
    }
}
