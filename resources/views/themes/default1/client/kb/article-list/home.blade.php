@extends('themes.default1.client.layout.client')

@section('title')
Knowledge Base -
@stop

@section('knowledgebase')
class = "active"
@stop
@section('content')

@if(Session::has('success'))
<div class="alert alert-success alert-dismissable">
    <i class="fa  fa-check-circle"></i>
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {{Session::get('success')}}
</div>
@endif
<!-- failure message -->
@if(Session::has('fails'))
<div class="alert alert-danger alert-dismissable">
    <i class="fa fa-ban"></i>
    <b>{!! Lang::get('lang.alert') !!}!</b>
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {{Session::get('fails')}}
</div>
@endif

<div id="content" class="site-content col-md-9">
    <div class="row">
        @foreach($categorys as $category)
        {{-- get the article_id where category_id == current category --}}
        <?php
            if ($category->parent > 0) continue;
            if (!$category->isVisibleForUser(Auth::user())) continue;
            $count = App\Model\kb\Relationship::where('category_id', '=', $category->id)->count();
            $children = $categorys->where('parent', '=', $category->id);
        ?>
        <div class="col-md-6">
            <section class="box-categories">
                <h1 class="section-title h4 clearfix">
                    <i class="fa fa-folder-open-o fa-fw text-muted"></i>
                    <small class="pull-right"><i class="fa fa-hdd-o fa-fw"></i></small>
                    <a href="{{url('category-list/'.$category->slug)}}" class="">{{$category->name}}({{$count}})</a>
                </h1>
                <ul class="fa-ul">
                    @forelse($children as $c_cat)
                        <?php
                            if (!$c_cat->isVisibleForUser(Auth::user())) continue;
                            $count = App\Model\kb\Relationship::where('category_id', '=', $c_cat->id)->count();
                        ?>
                        <li>
                            <i class="fa-li fa fa-list-alt fa-fw text-muted"></i>
                            <h3 class="h5">
                                <a href="{{url('category-list/'.$c_cat->slug)}}" class="">{{$c_cat->name}}({{$count}})</a>
                            </h3>
                        </li>
                    @empty
                        <p>{!! Lang::get('lang.no_subcategories') !!}</p>
                    @endforelse
                </ul>
            </section>
        </div>
        @endforeach
    </div>
    <section class="section">
        <div class="banner-wrapper banner-horizontal clearfix">
            <h4 class="banner-title h4">{!! Lang::get('lang.need_more_support') !!}?</h4>
            <div class="banner-content">
                <p>{!! Lang::get('lang.if_you_did_not_find_an_answer_please_raise_a_ticket_describing_the_issue') !!}.</p>
            </div>
            <p><a href="{!! URL::route('form') !!}" class="btn btn-custom">{!! Lang::get('lang.submit_a_ticket') !!}</a></p>
        </div>
    </section>
</div>
@stop

@section('category')
<h2 class="section-title h4 clearfix">{!! Lang::get('lang.categories') !!}<small class="pull-right"><i class="fa fa-hdd-o fa-fw"></i></small></h2>
<ul class="nav nav-pills nav-stacked nav-categories">
    @foreach($categorys as $category)
    <?php
    if ($category->parent != 0) continue;
    if (!$category->isVisibleForUser(Auth::user())) continue;
    $num = \App\Model\kb\Relationship::where('category_id', '=', $category->id)->get();
    $article_id = $num->pluck('article_id');
    $numcount = count($article_id);
    ?>
    <li><a href="{{url('category-list/'.$category->slug)}}"><span class="badge pull-right">{{$numcount}}</span>{{$category->name}}</a></li>
    @endforeach
</ul>
@stop
