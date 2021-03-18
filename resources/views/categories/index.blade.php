@extends('layouts.main')

@section('content')
    <div id="page-title">
        <h2>קטגוריות / קטגוריות משנה</h2>
        @if(session('message'))
            <p style="color: #b245f7;font-size: 35px;">{{ session('message') }}</p>
        @endif
    </div>
    <div class="row">
        <div class="panel">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" onclick="menuTab(this)" href="#home">קטגוריות</a></li>
                <li><a data-toggle="tab" onclick="menuTab(this)" href="#menu1">קטגוריות משנה</a></li>
            </ul>

            <div class="tab-content">
                <div id="home" class="tab-pane fade in active">
                    <div class="panel-body">
                        <a class="btn btn-info" onclick="createRow('#categoryModal','{{ route('categories.store') }}', '#categoriesList', true, 'categories','',null)">צור קטגוריה</a>
                        <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered datatable" id="categoriesList">
                            <thead>
                            <tr>
                                <th>שֵׁם</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td>{{ $category->name }}</td>
                                        <td>
                                            <form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <a class="btn btn-gray" onclick="createRow('#categoryModal','{{ route('categories.store') }}', '#categoriesList', true, 'categories', '{{$category->id}}', null)">לַעֲרוֹך</a>
                                                <button class="btn btn-danger">לִמְחוֹק</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="menu1" class="tab-pane fade">
                    <button class="btn btn-info btn-md"
                            onclick="createRow('#subCategoryModal','{{ route('subcategories.store') }}', '#subcategoriesList', true, 'subcategories','',null)">
                        צור תת קטגוריה
                    </button>
                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered datatable" id="categoriesList">
                        <thead>
                        <tr>
                            <th>שֵׁם</th>
                            <th>קטגוריה</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($subcategories as $subcategory)
                            <tr>
                                <td>{{ $subcategory->name }}</td>
                                <td>{{ $subcategory->category ? $subcategory->category->name : '' }}</td>
                                <td>
                                    <form action="{{ route('subcategories.destroy', $subcategory->id) }}" method="POST">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <a class="btn btn-gray" onclick="createRow('#subCategoryModal','{{ route('subcategories.store') }}', '#subcategoriesList', true, 'subcategories', '{{$subcategory->id}}', 'category_id')">לַעֲרוֹך</a>
                                        <button class="btn btn-danger">לִמְחוֹק</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <script type="text/javascript" src="/js/jobs.js"></script>

@endsection
