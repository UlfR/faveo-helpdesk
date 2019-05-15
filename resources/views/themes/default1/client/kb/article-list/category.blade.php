@extends('themes.default1.client.layout.client')

<?php
use App\Model\kb\Relationship;
$category = App\Model\kb\Category::query()->find($id);
$parent_id = $category->parent;
$parent = App\Model\kb\Category::query()->find($parent_id);
$children = $categorys->where('parent', '=', $category->id);
?>
@section('title')
{!! $category->name !!} -
@stop

@section('kb')
class = "active"
@stop

@section('content')
<div id="content" class="site-content col-md-9">
    <header class="archive-header">
        <?php if ($parent) { ?>
        <h2 style="display: inline-block"><a href="{{url('category-list/'.$parent->slug)}}" class="">{{$parent->name}}</a></h2>
        <?php } ?>
        <h3 style="display: inline-block"> &bull; {!! $category->name !!}</h3>
    </header><!-- .archive-header -->
    <blockquote class="archive-description" style="display: none;">
        <p>{!! $category->description !!}</p>
    </blockquote>
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
        @endforelse
    </ul>

    <div class="archive-list archive-article">
        <?php foreach ($article_id as $id) { ?>
            <?php
            $article = App\Model\kb\Article::where('id', $id);
            if (!Auth::user() || Auth::user()->role == 'user') {
                $article = $article->where('status', 1);
                $article = $article->where('type', 1);
            }
            $article = $article->orderBy('publish_time', 'desc');
            $article = $article->get();
            ?>
            @forelse($article as $arti)
            <?php if (!$arti->isVisibleForUser(Auth::user())) continue; ?>
            <article class="hentry">
                <header class="entry-header">
                    <i class="fa fa-list-alt fa-2x fa-fw pull-left text-muted"></i>
                    <h2 class="entry-title h4"><a href="{{url('show/'.$arti->slug)}}" onclick="toggle_visibility('foo');">{{$arti->name}}</a></h2>
                </header><!-- .entry-header -->
                <?php $str = $arti->description; ?>
                <?php $excerpt = App\Http\Controllers\Client\kb\UserController::getExcerpt($str, $startPos = 0, $maxLength = 400); ?>
                <blockquote class="archive-description">
                    <?php $content = trim(preg_replace("/<img[^>]+\>/i", "", $excerpt), " \t.") ?>
                    <p>{!! strip_tags($content) !!}</p>
                    <a class="readmore-link" href="{{url('show/'.$arti->slug)}}">{!! Lang::get('lang.read_more') !!}</a>
                </blockquote>	
                <footer class="entry-footer">
                    <div class="entry-meta text-muted">
                        <span class="date"><i class="fa fa-clock-o fa-fw"></i> <time datetime="2013-10-22T20:01:58+00:00">{{$arti->created_at->format('l, d-m-Y')}}</time></span>
                    </div><!-- .entry-meta -->
                </footer><!-- .entry-footer -->
            </article><!-- .hentry -->
            @empty
            @endforelse
            <?php
        }
        //echo $all->render();
        ?>                      
    </div>
</div>
<script type="text/javascript">
<!--
    function toggle_visibility(id) {
        var e = document.getElementById(id);
        if (e.style.display == 'block')
            e.style.display = 'none';
        else
            e.style.display = 'block';
    }
//-->
</script>

@stop




@section('category')
<h2 class="section-title h4 clearfix">{!! Lang::get('lang.categories') !!}<small class="pull-right"><i class="fa fa-hdd-o fa-fw"></i></small></h2>
<ul class="nav nav-pills nav-stacked nav-categories">
    @foreach($categorys as $category)
    <?php
    if ($category->parent != $parent_id) continue;
    if (!$category->isVisibleForUser(Auth::user())) continue;
    $numcount = Relationship::where('category_id','=', $category->id)->count();
    ?>
    <li><a href="{{url('category-list/'.$category->slug)}}"><span class="badge pull-right">{{$numcount}}</span>{{$category->name}}</a></li>
    @endforeach
</ul>
@stop