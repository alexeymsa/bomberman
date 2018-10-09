<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\Lobby\OpenSessionRequest;
use App\Http\Requests\Lobby\CloseSessionRequest;
use App\Models\GameBet;
use App\Models\Game;
use App\Models\GameSession;
use DB;
use Illuminate\Support\Facades\Gate;
use Auth;
use Session;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;

class LobbyController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $games = Game::all();

        $user = \Auth::user();
        if ($user != null)
        {
            $encodedChatId = '42bgw';
            $siteDomain = 'gamechainger.io';
            $siteUserExternalId = $user->id;
            $siteUserFullName = substr(Auth::user()->email, 0, strrpos(Auth::user()->email, '@'));
            $secretKey = env("CHAT_KEY");

            $signatureDataParts = $siteDomain.$siteUserExternalId.$siteUserFullName.$secretKey;
            $hash = md5($signatureDataParts);
            $name = substr(Auth::user()->email, 0, strrpos(Auth::user()->email, '@'));
        } else {
            $hash = null;
            $name = null;
        }

        return view('lobby.index', compact('games', 'hash', 'name'));
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getGames()
    {
        return view('lobby.games');
    }

    public function gameHistory()
    {
        if (! Gate::allows('session_access')) {
            return abort(401);
        }

        $sessions = GameSession::select([
            'gs.id',
            'gs.win',
            'gs.winner_id',
            'gs.started_at',
            'gs.ended_at',
            'gb.bet',
            'g.name',
            'gsu.credits_before',
            'gsu.credits_after',
            DB::raw('(SELECT COUNT(`gsu2`.`id`) FROM `game_sessions` as `gs2`
                left join `game_sessions_users` as `gsu2` on `gs2`.`id` = `gsu2`.`session_id` 
                WHERE `gs2`.`id` = `gs`.`id`
                GROUP BY `gs2`.`id`
                ) as count'),
            'u.email'
        ])
            ->from('game_sessions as gs')
            ->where('gsu.user_id', Auth::id())
            ->leftJoin('game_sessions_users as gsu', function ($j){
                $j->on('gs.id', '=', 'gsu.session_id');
            })->leftJoin('users as u', function ($j){
                $j->on('gs.winner_id', '=', 'u.id');
            })->leftJoin('game_bets as gb', function ($j){
                $j->on('gs.bet_id', '=', 'gb.id');
            })->leftJoin('games as g', function ($j){
                $j->on('gb.game_id', '=', 'g.id');
            })
            ->get()
            ->all();


        $games = Game::all();

        return view('lobby.history', compact('sessions', 'games'));
    }

    public function tournaments()
    {
        $bets = GameBet::with(['game'])
            ->get()
            ->all();

        $games = Game::all();


        return view('lobby.tournaments', compact('bets', 'payment_history', 'games'));
    }

    public function removeUserFromSession()
    {

    }


}
