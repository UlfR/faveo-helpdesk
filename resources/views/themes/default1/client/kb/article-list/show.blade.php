<?php
use App\Model\kb\Relationship;
$all = App\Model\kb\Relationship::query()->where('article_id','=', $arti->id)->get();
$category_id = $all->pluck('category_id')->first();
$category = App\Model\kb\Category::query()->find($category_id);
?>

@extends('themes.default1.client.layout.client')
@section('breadcrumb')
<div class="site-hero clearfix">
    <ol class="breadcrumb breadcrumb-custom">
        <li class="text">{!! Lang::get('lang.you_are_here') !!}: </li>
        <li><a href="{{url('/')}}">{!! Lang::get('lang.home') !!}</a></li>
        <li><a href="{{url('/knowledgebase')}}">{!! Lang::get('lang.knowledge_base') !!}</a></li>
        <li><a href="{{url('category-list/'.$category->slug)}}">{{$category->name}}</a></li>
        <li class="active">{{$arti->name}}</li>
    </ol>
</div>
@stop	
@section('title')
    {!! $arti->name !!} -
@stop	
@section('content')
<div id="content" class="site-content col-md-9">
    <article class="hentry">
        <header class="entry-header">
            <h1 class="entry-title">{{$arti->name}}</h1>

            <div class="entry-meta text-muted">
                <span class="date"><i class="fa fa-film fa-fw"></i> <time datetime="2013-09-19T20:01:58+00:00">{{$arti->created_at->format('l, d-m-Y')}}</time></span>
                <span class="category"><i class="fa fa-folder-open-o fa-fw"></i> <a href="{{url('category-list/'.$category->slug)}}">{{$category->name}}</a></span>
            </div><!-- .entry-meta -->
        </header><!-- .entry-header -->

        <div class="entry-content clearfix">

            <p>{!!$arti->description!!}</p>

        </div><!-- .entry-content -->

    </article><!-- .hentry -->
    <?php $comments = []; #App\Model\kb\Comment::where('article_id', $arti->id)->where('status', '1')->get(); ?>

    <div id="comments" class="comments-area">
        @foreach($comments as $comment)
        <ol class="comment-list">
            <li class="comment">
                <article class="comment-body">
                    <footer class="comment-meta">
                        <div class="comment-author">
                            <img src="{{asset("lb-faveo/media/images/avatar_1.png")}}" alt="" height="50" width="50" class="avatar" />
                            <b class="fn"><a href="#" rel="external" class="url">{!! $comment->name !!}</a></b>
                        </div><!-- .comment-author -->

                        <div class="comment-metadata">
                            <small class="date text-muted">
                                <time datetime="2013-10-23T01:50:50+00:00">{!! $comment->created_at->format('l, d-m-Y') !!}</time>
                            </small>
                        </div><!-- .comment-metadata -->
                    </footer><!-- .comment-meta -->

                    <div class="comment-content">

                        <p>{!! $comment->comment !!}</p>
                    </div><!-- .comment-content -->

                    <div class="comment-reply text-right">
                        {{-- <a class="btn btn-custom btn-sm" href="#">Reply</a> --}}
                    </div><!-- .comment-reply -->
                </article><!-- .comment-body -->
            </li><!-- .comment -->
        </ol><!-- .comment-list -->
        @endforeach
        <!-- div id="respond" class="comment-respond form-border">
            <h3 id="reply-title" class="comment-reply-title section-title">{!! Lang::get('lang.leave_a_reply') !!}</h3>
            {!! Form::open(['method'=>'post','url'=>'postcomment/'.$arti->slug]) !!}
            <div class="row">
                <div class="form-group">
                    <div class="col-md-4">
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            {!! Form::label('name',Lang::get('lang.name')) !!}
                            {!! $errors->first('name', '<spam class="help-block" style="red">:message</spam>') !!}
                            {!! Form::text('name',null,['class' => 'form-control']) !!}
                        </div>
                        <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                            {!! Form::label('email',Lang::get('lang.email')) !!}
                            {!! $errors->first('email', '<spam class="help-block">:message</spam>') !!}
                            {!! Form::text('email',null,['class' => 'form-control']) !!}
                        </div>
                        <div class="form-group {{ $errors->has('website') ? 'has-error' : '' }}">
                            {!! Form::label('website',Lang::get('lang.website')) !!}
                            {!! $errors->first('website', '<spam class="help-block">:message</spam>') !!}
                            {!! Form::text('website',null,['class' => 'form-control']) !!}
                        </div>
                        <button type="submit" class="btn btn-custom btn-lg">{!! Lang::get('lang.post_message') !!}</button>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group {{ $errors->has('comment') ? 'has-   error' : '' }}">
                            {!! Form::label('comment',Lang::get('lang.message')) !!}
                            {!! $errors->first('comment', '<spam class="help-block">:message</spam>') !!}
                            {!! Form::textarea('comment',null,['class' => 'form-control','size' => '30x8','id'=>'comment']) !!}
                        </div>
                    </div>												
                </div>
            </div>
          

            {!! Form::close() !!}
        </div--><!-- #respond -->
    </div>
</div>						

<script type="text/javascript">
        $(function () {
            $("textarea").wysihtml5();
        });
</script>

@stop

@section('title')
    @if(isset($category->name))
        {!! $category->name !!} -
    @endif
@stop

@section('category')
<h2 class="section-title h4 clearfix">{!! Lang::get('lang.categories') !!}<small class="pull-right"><i class="fa fa-hdd-o fa-fw"></i></small></h2>
<ul class="nav nav-pills nav-stacked nav-categories">

<?php $categorys = App\Model\kb\Category::all(); ?>
    @foreach($categorys as $cat)
<?php
        if ($cat->parent != $category->parent) continue;
        if (!$cat->isVisibleForUser(Auth::user())) continue;
        $numcount = Relationship::where('category_id','=', $cat->id)->count();
?>
    <li><a href="{{url('category-list/'.$cat->slug)}}"><span class="badge pull-right">{{$numcount}}</span>{{$cat->name}}</a></li>
    @endforeach
</ul>
@stop
