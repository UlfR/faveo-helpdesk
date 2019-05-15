@extends('themes.default1.client.layout.client')

@section('title')
Category List -
@stop

@section('kb')
class = "active"
@stop

@section('content')
<div id="content" class="site-content col-md-12">
    <!-- Start of Page Container -->
    <div class="row home-listing-area">
        <div class="span8">
            <h2>{!! Lang::get('lang.categories') !!}</h2>
        </div>
    </div>
    <div class="row separator">
        @foreach($categorys as $category)
        <?php
            if ($category->parent > 0) continue;
            if (!$category->isVisibleForUser(Auth::user())) continue;
            $count = App\Model\kb\Relationship::where('category_id', '=', $category->id)->count();
            $children = $categorys->where('parent', '=', $category->id);
        ?>
        <div class="col-xs-6">
            <section class="articles-list">
                <h3>
                    <i class="fa fa-folder-open-o fa-fw text-muted"></i>
                    <a href="{{url('category-list/'.$category->slug)}}">{{$category->name}}</a>
                    <span>({{$count}})</span>
                </h3>
                <div>{!! $category->description !!}</div>
                <ul class="articles">
                    <hr>
                    @forelse($children as $c_cat)
                        <?php
                        if (!$c_cat->isVisibleForUser(Auth::user())) continue;
                        $count = App\Model\kb\Relationship::where('category_id', '=', $c_cat->id)->count();
                        ?>
                        <li class="article-entry image" style="margin-left: 50px;">
                            <h4>
                                <a href="{{url('category-list/'.$c_cat->slug)}}" class="">{{$c_cat->name}}({{$count}})</a>
                            </h4>
                        </li>
                        @empty
                        <li>{!! Lang::get('lang.no_subcategories') !!}</li>
                        @endforelse
                </ul>
            </section>
        </div>
        @endforeach
    </div>
</div>
<!-- end of page content -->
@stop

