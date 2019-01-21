# DRUPAL 8 MODULE : DCOS aka Display children or siblings 
## GOAL

Given this 6 basic pages and Given a menu sorted like this : 

* Menu item Page Head 1 -> Page Head 1
	* Menu item Page Child 1 -> Page Child 1
	* Menu item Page Child 2 -> Page Child 2
* Menu item Page Head 2 -> Page Head 2
	* Menu item Page Child 3 -> Page Child 3
	* Menu item Page Child 4 -> Page Child 4

Placing a block on the content-type "Basic page" : 
* viewing 'Page Head 1' will display 'Page Child 1' && 'Page Child 2'
* viewing 'Page Head 2' will display 'Page Child 3' && 'Page Child 4'
* viewing 'Page Child 1' will display 'Page Child 2'
* viewing 'Page Child 4' will display 'Page Child 3'

## USAGE

* enable module (eventually rename root folder of the module `dcos` instead of `drupal-dcos`)
* go to admin/structure/block
* Place "Docs Block" in the right section
	* Enter the menu machine-name you want to watch : ex 'main' or 'my-footer-menu'
	* Enter the viewmode you want to display: ex 'teaser'
	* Configure the block to react only to certain type of node (ex basicpages)

* save


## POSSIBLE IMPROVMENTS

* Block config UI : select menu instead of simple texfiled



## KNOWN LIMITATION 

Is actually limited to only 2-levels menu.

