<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuizzesResource;
use App\Http\Resources\UsersResource;
use App\Quiz;
use App\QuizAnswer;
use App\Role;
use App\UserQuiz;
use Illuminate\Http\Request;
use JWTAuth;
class QuizzesController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::all();
        return view('quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $roles = Role::where('is_simple_user', 1)->get();
        return view('quizzes.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $rules = [
            'role_id' => 'required',
            'question' => 'required|min:3',
            'type' => 'required',
        ];
        $this->validate($request, $rules);
        $data = $request->all();
        $this->_main_control_block($data);
        return redirect(route('quizzes.index'))->with('message', 'Quiz created successfully');
    }

    public function edit($id)
    {
        $quiz = Quiz::find($id);
        $roles = Role::where('is_simple_user', 1)->get();
        return view('quizzes.edit', compact('quiz', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'role_id' => 'required',
            'question' => 'required|min:3',
            'type' => 'required',
        ];
        $this->validate($request, $rules);
        $data = $request->all();
        $quiz = Quiz::find($id);
        $this->_main_control_block($data, $quiz);
        return redirect(route('quizzes.index'))->with('message', 'Quiz updated successfully');

    }

    public function _main_control_block($data, $quiz = null)
    {
        if (!$quiz) {
            $quiz = new Quiz();
        }
        $quiz->role_id = $data['role_id'];
        $quiz->question = $data['question'];
        $quiz->type = $data['type'];
        $quiz->save();
        $quiz->answers()->delete();
        if ($data['type']) {
            $answer = new QuizAnswer();
            $answer->quiz_id = $quiz->id;
            $answer->answer = $data['answer'];
            $answer->value_answer = $data['value_answer'];
            $answer->value = $data['value'];
            $answer->save();
        } else {
            for ($i = 1; $i <= $data['answers_count']; $i++) {
                $answer = new QuizAnswer();
                $answer->quiz_id = $quiz->id;
                $answer->answer = $data['answer_' . $i];
                $answer->save();
            }
        }
    }

    public function getByRoleID($role_id)
    {
        $quizzes = Quiz::where('role_id', $role_id)->get();
        return response()->json([
            'data' => QuizzesResource::collection($quizzes),
            'count' => $quizzes->count()
        ]);
    }

    public function saveUserAnswer(Request $request, $answer_id)
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }
        $data = $request->all();
        $answer = QuizAnswer::find($answer_id);
        if(!$userQuiz = UserQuiz::where('quiz_answer_id',$answer_id)->where('user_id',$user->id)->first()) {
            $userQuiz = new UserQuiz();
        }
        $userQuiz->user_id = $user->id;
        $userQuiz->quiz_id = $answer->quiz_id;
        $userQuiz->quiz_answer_id = $answer->id;
        $userQuiz->value = $answer->value ? $data['value'] : null;
        $userQuiz->save();
        return response()->json(new UsersResource($user), 201);
    }
}
