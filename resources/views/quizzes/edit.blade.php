@extends('layouts.main')

@section('content')
    <style>
        .answer-single-block {
            border: 1px solid gray;
        }
    </style>
    <div id="page-title">
        <h2>Create Quiz</h2>
        {{--<p>The most complete user interface framework that can be used to create stunning admin dashboards--}}
        {{--and presentation websites.</p>--}}
    </div>

    <div class="panel">
        @if($errors->any())
            {!! implode('', $errors->all('<p style="color:red">:message</p>')) !!}
        @endif
        <div class="panel-body">
            <div class="row">
                <form action="{{ route('quizzes.update',$quiz->id) }}" enctype="multipart/form-data" method="POST">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="form-group">
                        <label class="control-label">User Type: </label>
                        <div class="col-sm-12">
                            <select class="form-control" name="role_id">
                                @foreach($roles as $role)
                                    @if(old('role_id') == $role->id)
                                        <option selected value="{{ $role->id }}">{{ $role->name }}</option>
                                    @else
                                        @if($quiz->role_id == $role->id)
                                        <option selected value="{{ $role->id }}">{{ $role->name }}</option>
                                        @else
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endif
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Question: </label>
                        <div class="col-sm-12">
                            <textarea class="form-control" name="question">{{ old('question') ? old('question') : $quiz->question }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Type:</label>
                        <div class="col-sm-12">
                            <select class="form-control" onchange="changeType(this)" name="type">
                                <option></option>
                                @foreach(\App\Quiz::TYPES as $type => $is_with_value)
                                    @if(is_string(old('type')) && old('type') == $is_with_value)
                                        <option selected value="{{ $is_with_value }}">{{ $type }}</option>
                                    @else
                                        @if($quiz->type == $is_with_value)
                                        <option selected value="{{ $is_with_value }}">{{ $type }}</option>
                                        @else
                                        <option value="{{ $is_with_value }}">{{ $type }}</option>
                                        @endif
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group answers-block">

                        <div class="row answer-single-block" data-type="1" style="{{ old('type') ? '' : ($quiz->type ? '' : 'display: none') }}">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Answer: </label>
                                    <textarea class="form-control" name="answer">{{ old('answer') ? old('answer') : $quiz->answers()->first()->answer}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Value Answer: </label>
                                    <textarea class="form-control"
                                              name="value_answer">{{ old('value_answer')  ? old('value_answer') : $quiz->answers()->first()->value_answer}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Value: </label>
                                    <input type="number" class="form-control" name="value"
                                           value="{{ old('value')  ? old('value') : $quiz->answers()->first()->value}}">
                                </div>
                            </div>
                        </div>
                        <div class="row answer-single-block" data-type="0" style="{{ is_string(old('type')) && old('type') == 0 ? '' : (!$quiz->type ? '' : 'display: none') }}">
                            <input type="hidden" name="answers_count"
                                   value="{{ old('answers_count') ? old('answers_count') : (!$quiz->type ? $quiz->answers()->count() : 1) }}">
                            <button type="button" class="btn btn-primary" onclick="addNewAnswer(self)"><i
                                class="glyph-icon icon-plus"></i></button>
                            @if( old('answers_count'))
                                @for($i = 1; $i <= old('answers_count'); $i++)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Answer: </label>
                                            <textarea class="form-control" name="answer_{{$i}}">{{ old('answer_'.$i) }}</textarea>
                                        </div>
                                    </div>
                                @endfor
                            @else
                                @if(!$quiz->type)
                                <?php $i = 1; ?>
                                @foreach($quiz->answers as $answer)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Answer: </label>
                                            <textarea class="form-control" name="answer_{{ $i }}">{{ $answer->answer }}</textarea>
                                        </div>
                                    </div>
                                <?php $i++; ?>
                                @endforeach
                                @else
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Answer: </label>
                                            <textarea class="form-control" name="answer_1">{{ old('answer_1') }}</textarea>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary">צור ארגון</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="/js/quiz.js"></script>

@endsection
