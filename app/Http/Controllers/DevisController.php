<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class DevisController extends Controller {
 public function index(){ return view('devis.index');}
 public function create(){ return view('devis.create');}
}
