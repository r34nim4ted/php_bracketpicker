<?php

namespace App\Strategies;

use Log;
use App\Game;
use App\Team;
use App\Bracket;
use App\Strategies\IValidateBracketStrategy;
use App\Repositories\TeamRepository;
use App\Factories\BracketFactory;
use Illuminate\Http\Request;
use App\Exceptions\BracketValidationException;

/**
 * Base implementation of IValidateBracketStrategy
 *
 */
class ValidateBaseBracketStrategy implements IValidateBracketStrategy
{

    /**
     * bracket exception codes
     */
    const BRACKET_EXISTS = 0;
    const NOT_MASTER = 1;
    const ROUND_EXISTS = 2;
    const ROUND_GAME_COUNT = 3;
    const TEAM_EXISTS = 4;
    const TEAM_NOT_TBD = 5;
    const TEAMS_NOT_SAME = 6
    const LEAVES_MATCH_MASTER = 7;
    const WINNER_IN_NEXT = 8;
    const WINNER_FROM_TEAMS = 9;
    const USER_EXISTS = 10;
    const USER_MATCHES_OWNER = 11;
    const NAME_SET = 12;
    const HALT = 13;



    /**
     * Allowed exceptions
     */
    protected $allowList;
    protected $teamRepo;

    /**
     * Create a new validator instance
     *
     * @param  TeamRepository  $teams
     * @return void
     */
    public function __construct(TeamRepository $teams)
    {
        $this->teamRepo = $teams;
        $this->allowList = collect([]);
    }

    /**
     * Validate bracket from request
     *
     * @param Request  $req
     * @return error collection
     */
    public function validate(Request $req)
    {
        $errors = collect([]);
        try {
            Log::info($req);
            $this->checkAssertion(array($this,'assertValidBracketId'),[$req->bracket_id],false,$errors);
            $this->checkAssertion(array($this,'assertNotMaster'),[$req->bracket_id],false,$errors);
            $master = BracketFactory::reverseBracket(Bracket::where('master',1)->first(), new ReverseBaseBracketStrategy());
            foreach ($master as $round=>$games) {
                $this->checkAssertion(array($this,'assertRoundIsSet'),[$req,$round],false,$errors);
                $this->checkAssertion(array($this,'assertGameCountCorrect'),[$req,$round,$master[$round]->count()],false,$errors);
                $round_games = $req->games[$round]
                foreach($i=0;i<count($games); i++) {
                    $game= $round_games[$i];
                    $this->checkAssertion(array($this,'assertTeamsExist'),[$game],true,$errors);
                    $this->checkAssertion(array($this,'assertTeamsNotTBD'),[$game],true,$errors);
                    $this->checkAssertion(array($this,'assertTeamsDifferent'),[$game],true,$errors);
                    if ($round == 1) {
                        $this->checkAssertion(array($this,'assertGamesMatch'),[$game,$master->$round->get($i)],false,$errors);
                    } else {
                        $child_a = ;
                        $child_b = ;
                        $this->checkAssertion(array($this,'assertTeamsChildWinners'),[$game,$child_a,$child_b],true, $errors);
                    }
                    $this->checkAssertion(array($this,'assertWinnerInTeams'),[$game],true,$errors);
                }
            }
            $this->checkAssertion(array($this,'assertUserNotNull'),[$req->user_id],true,$errors);
            $this->checkAssertion(array($this,'assertUserIsOwner'),[$req->user_id],true,$errors);
            $this->checkAssertion(array($this,'assertBracketNamed'),[$req->name],true,$errors);
        } catch (BracketValidationException $e) {
            Log::warning('bracket did not pass validation');
            Log::warning($e->getMessage());
        }
        return $errors;
    }


    public function checkAssertion($assertion, $arguments, $continue, $errors)
    {
        $success = false;
        try {
            $assertion($arguments);
            $success = true;
        } catch (BracketValidationException $e) {
            if ($this->allowList->has($e->getCode())) {
                Log::info('BracketValidationException '.$e->getCode().' allowed with reason: \''.$this->allowList->get($e->getCode()).'\'.');
                $success = true;
            } else {
                $errors->push($e->getMessage());
                // log disallowed exception
            }
        }
        if (!($success || $continue)) {
            throw new BracketValidationException('Validation failed and continue false', $this::HALT);
        }
    }

    /* ASSERTIONS */

    public function assertValidBracketId($args)
    {
        $bracket_id = $args[0];
        $b = Bracket::where('bracket_id',$bracket_id)->first();
        if (!isset($b)) {
            throw new BracketValidationException('Bracket not found for id: \''.$bracket_id.'\'.', $this::BRACKET_EXISTS);
        }
        Log::debug('BracketId set to \''$bracket_id.'\'.');
    }

    public function assertNotMaster($args)
    {
        $bracket_id = $args[0];
        $master = Bracket::where('master',1)->first();
        if($master->bracket_id == $bracket_id) {
            throw new BracketValidationException('Bracket is master', $this::NOT_MASTER);
        }
        Log::debug('Bracket with id \''.$bracket_id.'\' is not master');
    }

    public function assertRoundIsSet($args)
    {
        $req = $args[0];
        $round = $args[1];
        $games = $req->games[$round];
        if(!isset($games)) {
            throw new BracketValidationException('Round '.$round.' not found',$this::ROUND_EXISTS);
        }
        Log::debug('Request contains round '.$round.'.');
    }

    public function assertGameCountCorrect($args)
    {
        $req = $args[0];
        $round = $args[1];
        $game_count = $args[2];
        $games = $req->games[$round];
        if(count($games) != $game_count) {
            throw new BracketValidationException('Round '.$round.' should have '.$game_count.' games, found '.count($games).'.',$this::ROUND_GAME_COUNT);
        }
        Log::debug('Round '.$round.' correctly has '.$game_count.' games.');
    }

    public function assertTeamsExist($args)
    {
        $game = $args[0];
        $team_a = $this->teamRepo->byName($game['T1']);
        $team_b = $this->teamRepo->byName($game['T2']);
        $winner = $this->teamRepo->byName($game['W']);
        if (!isset($team_a)) {
            throw new BracketValidationException('Team 1 with name '.$game['T1'].' doesn\'t exist', $this::TEAM_EXISTS);
        } else if (!isset($team_b)) {
            throw new BracketValidationException('Team 2 with name '.$game['T2'].' doesn\'t exist', $this::TEAM_EXISTS);
        } else if (!isset($winner)) {
            throw new BracketValidationException('Winner with name '.$game['W'].' doesn\'t exist', $this::TEAM_EXISTS);
        }
        Log::debug('Team 1: \''.$game['T1'].'\', Team 2: \''.$game['T2'].'\', Winner: \''.$game['W'].'\' all exist.');
    }

    public function assertTeamsNotTBD($args)
    {
        $game = $args[0];
        $team_a = $this->teamRepo->byName($game['T1']);
        $team_b = $this->teamRepo->byName($game['T2']);
        $winner = $this->teamRepo->byName($game['W']);
        if ($team_a->name == 'TBD') {
            throw new BracketValidationException('Team 1 with id '.$team_a->team_id.' is TBD', $this::TEAM_NOT_TBD);
        } else if ($team_b->name == 'TBD') {
            throw new BracketValidationException('Team 2 with id '.$team_b->team_id.' is TBD', $this::TEAM_NOT_TBD);
        } else if ($winner->name == 'TBD') {
            throw new BracketValidationException('Winner with id '.$winner->team_id.' is TBD', $this::TEAM_NOT_TBD);
        }
        Log::debug('Team 1: \''.$team_a->team_id.'\', Team 2: \''.$team_b->team_id.'\', Winner: \''.$winner->team_id.'\' all not TBD.');
    }

    public function assertTeamsDifferent($args)
    {
        $game = $args[0];
        $team_a = $this->teamRepo->byName($game['T1']);
        $team_b = $this->teamRepo->byName($game['T2']);
        if ($team_a->name == $team_b->name) {
            throw new BracketValidationException('Team 1 with id '.$team_a->team_id.' has same name as Team 2 with id \''.$team_b->team_id.'\'.', $this::TEAMS_NOT_SAME);
        }
        Log::debug('Team 1: \''.$team_a->team_id.'\', Team 2: \''.$team_b->team_id.'\' have different names');
    }

    public function assertGamesMatch($args)
    {
        $game1 = $args[0];
        $team1_a = $this->teamRepo->byName($game1['T1']);
        $team1_b = $this->teamRepo->byName($game1['T2']);
        $game2 = $args[1];
        $team2_a = $this->teamRepo->byName($game2['T1']);
        $team2_b = $this->teamRepo->byName($game2['T2']);
        if ($team1_a->team_id != $team2_a->team_id) {
            throw new BracketValidationException('Request Team 1 with name '.$team1_a->name.
                ' doesn\'t have same name as Master Team 1 with name \''.$team2_a->name.'\'.', $this::LEAVES_MATCH_MASTER);
        } else if ($team1_b->team_id != $team2_b->team_id) {
            throw new BracketValidationException('Request Team 2 with name '.$team1_b->name.
                ' doesn\'t have same name as Master Team 2 with name \''.$team2_b->name.'\'.', $this::LEAVES_MATCH_MASTER);
        }
        Log::debug('Team 1: \''.$team_a->team_id.'\', Team 2: \''.$team_b->team_id.'\' match master');
    }

    public function assertTeamsChildWinners($args)
    {
        $game = $args[0];
        $team_a = $this->teamRepo->byName($game['T1']);
        $team_b = $this->teamRepo->byName($game['T2']);
        $child_a = $args[1];
        $winner_a = $this->teamRepo->byName($child_a['W']);
        $child_b = $args[1];
        $winner_b = $this->teamRepo->byName($child_b['W']);
        if ($team_a->name == $winner_a->name) {
            throw new BracketValidationException('Team 1 with id '.$team_a->team_id.' has same name as Child 1 with id \''.$child_a->team_id.'\'.', $this::WINNER_IN_NEXT);
        } else if ($team_b->name == $winner_b->name) {
            throw new BracketValidationException('Team 2 with id '.$team_b->team_id.' has same name as Child 2 with id \''.$child_b->team_id.'\'.', $this::WINNER_IN_NEXT);
        }
        Log::debug('Team 1: \''.$team_a->team_id.'\', Team 2: \''.$team_b->team_id.'\' Children winners');

    }

    public function assertWinnerInTeams($args)
    {
        $game = $args[0];
        $team_a = $this->teamRepo->byName($game['T1']);
        $team_b = $this->teamRepo->byName($game['T2']);
        $winner = $this->teamRepo->byName($game['W']);
        if( !($winner->team_id == $team_a->team_id || $winner->team_id == $team_b->team_id)) {
            throw new BracketValidationException('Winner: \''.$winner->team_id.'\' not one of Team 1 with id '
                .$team_a->team_id.', Team 2 with id \''.$team_b->team_id.'\'.', $this::WINNER_FROM_TEAMS);
        }
        Log::debug('Winner: \''.$winner->name.'\' is one of Team 1: \''.$team_a->name.'\', Team 2: \''.$team_b->name.'\'');
    }

        //      assert $req_game->winner  in $req_game->teams
        //   endforeach
        // end foreach

    public function assertUserNotNull($args)
    {
        $user_id = $args[0];
        $user = User::where('user_id',$user_id)->first();
        if (!isset($user)) {
            throw new BracketValidationException('User with id \''.$user_id.'\' was not found.', $this::USER_EXISTS);
        }
        Log::debug('User with id \''.$user_id.'\' found with name \''.$user->name.'\'.');
    }

    public function assertUserIsOwner($args)
    {
        $user_id = $args[0];
        $user = User::where('user_id',$user_id)->first();
        $auth = Auth::user();
        $owner = Bracket::where('user_id',$args[1])->first()->user;
        if ($user->user_id != $auth->user_id ||) {
            throw new BracketValidationException('Form user with id \''.$user->user_id.
                '\' doesn\'t match authenticated user with id \''.$auth->user_id.'\'.', $this::USER_MATCHES_OWNER);
        } else if ( $user->user_id != $owner->user_id ) {
            throw new BracketValidationException('Form user with id \''.$user->user_id.
                '\' doesn\'t match Bracket owner with id \''.$owner->user_id.'\'.', $this::USER_MATCHES_OWNER);
        } else if ( $auth->user_id != $owner->user_id ) {
            throw new BracketValidationException('Authenticated user with id \''.$auth->user_id.
                '\' doesn\'t match Bracket owner with id \''.$owner->user_id.'\'.', $this::USER_MATCHES_OWNER);
        }
        Log::debug('Form user with id \''.$user->user_id.'\', Bracket owner with id,\''.$owner->user_id.
            '\', and Authenticated user with id \''.$auth->user_id.'\' all match.');
    }


    public function assertBracketNamed($args)
    {
        $user_id = $args[0];
        $user = User::where('user_id',$user_id)->first();
        if (!isset($user)) {
            throw new BracketValidationException('Winner with name '.$game['W'].' doesn\'t exist', $this::NAME_SET);
        }
        Log::debug('Round '.$round.' correctly has '.$game_count.' games.');
    }
}
