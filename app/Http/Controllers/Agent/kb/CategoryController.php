<?php

namespace App\Http\Controllers\Agent\kb;

// Controllers
use App\Http\Controllers\Agent\helpdesk\TicketController;
use App\Http\Controllers\Controller;
// Requests
use App\Http\Requests\kb\CategoryRequest;
use App\Http\Requests\kb\CategoryUpdate;
// Model
use App\Model\helpdesk\Agent\Department;
use App\Model\helpdesk\Agent\Teams;
use App\Model\helpdesk\Agent_panel\Organization;
use App\Model\kb\Category;
use App\Model\kb\Relationship;
// Classes
use App\Model\kb\Visibilities;
use Datatable;
use Exception;
use Lang;
use Redirect;

/**
 * CategoryController
 * This controller is used to CRUD category.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     * constructor to check
     * 1. authentication
     * 2. user roles
     * 3. roles must be agent.
     *
     * @return void
     */
    public function __construct()
    {
        // checking authentication
        $this->middleware('auth');
        // checking roles
        $this->middleware('roles');
        SettingsController::language();
    }

    /**
     * Indexing all Category.
     *
     * @param type Category $category
     *
     * @return Response
     */
    public function index()
    {
        /*  get the view of index of the catogorys with all attributes
          of category model */
        try {
            return view('themes.default1.agent.kb.category.index');
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * fetching category list in chumper datatables.
     *
     * @return type chumper datatable
     */
    public function getData()
    {
        /* fetching chumper datatables */
        return Datatable::collection(Category::All())
                        /* search column name */
                        ->searchColumns('name')
                        /* order column name and description */
                        ->orderColumns('name', 'description')
                        /* add column name */
                        ->addColumn('name', function ($model) {
                            $string = strip_tags($model->name);

                            return str_limit($string, 20);
                        })
                        /* add column Created */
                        ->addColumn('Created', function ($model) {
                            $t = $model->created_at;

                            return TicketController::usertimezone($t);
                        })
                        /* add column Actions */
                        /* there are action buttons and modal popup to delete a data column */
                        ->addColumn('Actions', function ($model) {
                            return '<span  data-toggle="modal" data-target="#deletecategory'.$model->slug.'"><a href="#" ><button class="btn btn-danger btn-xs"></a>'.\Lang::get('lang.delete').'</button></span>&nbsp;<a href=category/'.$model->id.'/edit class="btn btn-warning btn-xs">'.\Lang::get('lang.edit').'</a>&nbsp;<a href=article-list class="btn btn-primary btn-xs">'.\Lang::get('lang.view').'</a>
				<div class="modal fade" id="deletecategory'.$model->slug.'">
        			<div class="modal-dialog">
            			<div class="modal-content">
                			<div class="modal-header">
                    			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    			<h4 class="modal-title">'.Lang::get('lang.are_you_sure_you_want_to_delete').'</h4>
                			</div>
                			<div class="modal-body">
                				'.$model->name.'
                			</div>
                			<div class="modal-footer">
                    			<button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="dismis2">'.Lang::get('lang.close').'</button>
                    			<a href="category/delete/'.$model->id.'"><button class="btn btn-danger">'.Lang::get('lang.delete').'</button></a>
                			</div>
            			</div>
        			</div>
    			</div>';
                        })
                        ->make();
    }

    /**
     * Create a Category.
     *
     * @param type Category $category
     *
     * @return type view
     */
    public function create(Category $category)
    {
        /* Get the all attributes in the category model */
        $category = $category->pluck('name', 'id')->toArray();
        /* get the view page to create new category with all attributes
          of category model */

        $orgs = Organization::query()->pluck('name', 'id')->toArray();
        $deps = Department::query()->pluck('name', 'id')->toArray();
        $teams = Teams::query()->pluck('name', 'id')->toArray();

        $iv_org_ids = [];
        $iv_dep_ids = [];
        $iv_team_ids = [];

        $nv_org_ids = [];
        $nv_dep_ids = [];
        $nv_team_ids = [];

        try {
            return view(
                'themes.default1.agent.kb.category.create',
                compact(
                    'category', 'orgs', 'deps', 'teams',
                   'iv_org_ids', 'iv_dep_ids', 'iv_team_ids',
                    'nv_org_ids', 'nv_dep_ids', 'nv_team_ids'
                )
            );
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * To store the selected category.
     *
     * @param type Category        $category
     * @param type CategoryRequest $request
     *
     * @return type Redirect
     */
    public function store(Category $category, CategoryRequest $request)
    {
        /* Get the whole request from the form and insert into table via model */
        $sl = $request->input('name');
        $slug = str_slug($sl, '-');
        $category->slug = $slug;
        // send success message to index page
        try {
            $category->fill($request->input())->save();
            $id = $category->id;

            $org_vises = [];
            foreach ($request->input('iv_org_ids') as $xid) {$org_vises[$xid] = true;}
            foreach ($request->input('nv_org_ids') as $xid) {$org_vises[$xid] = false;}

            $dep_vises = [];
            foreach ($request->input('iv_dep_ids') as $xid) {$dep_vises[$xid] = true;}
            foreach ($request->input('nv_dep_ids') as $xid) {$dep_vises[$xid] = false;}

            $team_vises = [];
            foreach ($request->input('iv_team_ids') as $xid) {$team_vises[$xid] = true;}
            foreach ($request->input('nv_team_ids') as $xid) {$team_vises[$xid] = false;}

            $visibilities = new Visibilities;
            $visibilities->setVisibilities('category', $id, 'org', $org_vises);
            $visibilities->setVisibilities('category', $id, 'dep', $dep_vises);
            $visibilities->setVisibilities('category', $id, 'team', $team_vises);

            return Redirect::back()->with('success', Lang::get('lang.category_inserted_successfully'));
        } catch (Exception $e) {
            return Redirect::back()->with('fails', Lang::get('lang.category_not_inserted').'<li>'.$e->getMessage().'</li>');
        }
    }

    /**
     * Show the form for editing the specified category.
     *
     * @param type          $slug
     * @param type Category $category
     *
     * @return type view
     */
    public function edit($id)
    {
        $category = Category::query()->find($id);
        $categories = Category::query()->pluck('name', 'id')->toArray();

        $orgs = Organization::query()->pluck('name', 'id')->toArray();
        $deps = Department::query()->pluck('name', 'id')->toArray();
        $teams = Teams::query()->pluck('name', 'id')->toArray();

        $visibilities = new Visibilities;
        $org_ids = $visibilities->getVisibilities('category', $id, 'org');
        $dep_ids = $visibilities->getVisibilities('category', $id, 'dep');
        $team_ids = $visibilities->getVisibilities('category', $id, 'team');

        $iv_org_ids = array_keys(array_filter($org_ids, function($v){return ($v == true);}));
        $iv_dep_ids = array_keys(array_filter($dep_ids, function($v){return ($v == true);}));
        $iv_team_ids = array_keys(array_filter($team_ids, function($v){return ($v == true);}));

        $nv_org_ids = array_keys(array_filter($org_ids, function($v){return ($v == false);}));
        $nv_dep_ids = array_keys(array_filter($dep_ids, function($v){return ($v == false);}));
        $nv_team_ids = array_keys(array_filter($team_ids, function($v){return ($v == false);}));

        return view(
            'themes.default1.agent.kb.category.edit',
            compact(
                'category',
                'categories', 'orgs', 'deps', 'teams',
                'iv_org_ids', 'iv_dep_ids', 'iv_team_ids',
                'nv_org_ids', 'nv_dep_ids', 'nv_team_ids'
            )
        );
    }

    /**
     * Update the specified Category in storage.
     *
     * @param type                $slug
     * @param type Category       $category
     * @param type CategoryUpdate $request
     *
     * @return type redirect
     */
    public function update($id, CategoryRequest $request)
    {

        /* Edit the selected category via id */
        $category = Category::where('id', $id)->first();
        $sl = $request->input('name');
        $slug = str_slug($sl, '-');
        /* update the values at the table via model according with the request */
        //redirct to index page with success message
        //    public function setVisibilities($entity_type, $entity_id, $part_type, $parts_info)
        try {
            $category->slug = $slug;
            $category->fill($request->input())->save();

            $org_vises = [];
            foreach ($request->input('iv_org_ids') as $xid) {$org_vises[$xid] = true;}
            foreach ($request->input('nv_org_ids') as $xid) {$org_vises[$xid] = false;}

            $dep_vises = [];
            foreach ($request->input('iv_dep_ids') as $xid) {$dep_vises[$xid] = true;}
            foreach ($request->input('nv_dep_ids') as $xid) {$dep_vises[$xid] = false;}

            $team_vises = [];
            foreach ($request->input('iv_team_ids') as $xid) {$team_vises[$xid] = true;}
            foreach ($request->input('nv_team_ids') as $xid) {$team_vises[$xid] = false;}

            $visibilities = new Visibilities;
            $visibilities->setVisibilities('category', $id, 'org', $org_vises);
            $visibilities->setVisibilities('category', $id, 'dep', $dep_vises);
            $visibilities->setVisibilities('category', $id, 'team', $team_vises);

            return redirect('category')->with('success', Lang::get('lang.category_updated_successfully'));
        } catch (Exception $e) {
            //redirect to index with fails message
            return redirect('category')->with('fails', Lang::get('lang.category_not_updated').'<li>'.$e->getMessage().'</li>');
        }
    }

    /**
     * Remove the specified category from storage.
     *
     * @param type              $id
     * @param type Category     $category
     * @param type Relationship $relation
     *
     * @return type Redirect
     */
    public function destroy($id, Category $category, Relationship $relation)
    {
        $relation = $relation->where('category_id', $id)->first();
        if ($relation != null) {
            return Redirect::back()->with('fails', Lang::get('lang.category_not_deleted'));
        } else {
            /*  delete the category selected, id == $id */
            $category = $category->whereId($id)->first();
            // redirect to index with success message
            try {
                $category->delete();

                return Redirect::back()->with('success', Lang::get('lang.category_deleted_successfully'));
            } catch (Exception $e) {
                return Redirect::back()->with('fails', Lang::get('lang.category_not_deleted').'<li>'.$e->getMessage().'</li>');
            }
        }
    }
}
