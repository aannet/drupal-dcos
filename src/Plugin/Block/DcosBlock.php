<?php

namespace Drupal\dcos\Plugin\Block;

use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Dcos' Block.
 * Display Children or Siblings from a menu 
 * The block has to be places on a node : this module retreive the 
 *
 * @Block(
 *   id = "dcos",
 *   admin_label = @Translation("Dcos  block"),
 *   category = @Translation("DCOS"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class DcosBlock extends BlockBase implements BlockPluginInterface {


	private $currentNid;

	private $menuTree;

	private $nidsToDisplay;



  	/**
  	 * [__construct description]
  	 * //TODO : place a 
  	 */
	public function __construct() {

		
	}

	/**
	 * [getMenuTree description]
	 * @return [type] [description]
	 */
	public function getMenuTree() {
		return $this->menuTree;
	}
	/**
	 * [getCurrentNid description]
	 * @return [type] [description]
	 */
	public function getCurrentNid() {
		return $this->currentNid;
	}
	/**
	 * [setMenuTree description]
	 * @param [type] $tree [description]
	 */
	public function setMenuTree( $tree ) {
		$this->menuTree = $tree;
	}
	/**
	 * [setcurrentNid description]
	 * @param  [type] $nid [description]
	 */
	public function setcurrentNid( $nid ) {
		$this->currentNid = $nid;
	}
	/**
	 * [getNidsToDisplay description]
	 * @return [type] [description]
	 */
	public function getNidsToDisplay() {
		return $this->nidsToDisplay;
	}
	/**
	 * [setNidsToDisplay description]
	 * @param [type] $nids [description]
	 */
	public function setNidsToDisplay( $nids ) {
		$this->nidsToDisplay = $nids;
	}


	/**
	* {@inheritdoc}
	*/
	public function blockForm($form, FormStateInterface $form_state) {
		$form = parent::blockForm($form, $form_state);

		$config = $this->getConfiguration();

		$form['dcos_block_menu_machine_name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Menu'),
			'#description' => $this->t('The menu you want to read hierarchy from'),
			'#default_value' => isset($config['dcos_block_menu_machine_name']) ? $config['dcos_block_menu_machine_name'] : '',
		];

		$form['dcos_block_contenttype_viewmode'] = [
			'#type' => 'textfield',
			'#title' => $this->t('View mode'),
			'#description' => $this->t('The viewmode you want to display'),
			'#default_value' => isset($config['dcos_block_contenttype_viewmode']) ? $config['dcos_block_contenttype_viewmode'] : '',
		];

		return $form;
	}

	/**
     * {@inheritdoc}
    */
	public function blockSubmit($form, FormStateInterface $form_state) {
		parent::blockSubmit($form, $form_state);
		$values = $form_state->getValues();
		$this->configuration['dcos_block_menu_machine_name'] = $form_state->getValue('dcos_block_menu_machine_name');
		$this->configuration['dcos_block_contenttype_viewmode'] = $form_state->getValue('dcos_block_contenttype_viewmode');
	}

	/**
	 * [getTreeMenu description]
	 * @param  [type] $menuName [description]
	 * @return [type]           [description]
	 */
	private function getTreeMenu( $menuName ) {
		$menuTree = \Drupal::menuTree();

        $parameters = $menuTree->getCurrentRouteMenuTreeParameters($menuName);
        $parameters->onlyEnabledLinks();
        $tree = $menuTree->load($menuName, $parameters);

        $manipulators = array(
            array('callable' => 'menu.default_tree_manipulators:checkNodeAccess'),
            array('callable' => 'menu.default_tree_manipulators:checkAccess'),
            array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort')
        );
        
        $tree = $menuTree->transform($tree,$manipulators);

        return $tree;
	}

	/**
	 * FIND CURRENT CHILDREN IF PARENT
	 * @return Array List of direct children NID
	 */
	private function findChildren() {
		$tree = $this->getMenuTree();
		$nid = $this->getCurrentNid();

		$subtrees = array();
        $currentNodeSubtree = array();
        $currentNodeChildren = array();

        foreach ($tree as $level1) {
            $routeIdLevel1 = $level1->link->getRouteParameters()['node'];
            if ($level1->subtree && $routeIdLevel1 == $nid) {
               foreach ($level1->subtree as $level2) {
                    $routeIdLevel2 = $level2->link->getRouteParameters()['node'];
                    $currentNodeChildren[] = $routeIdLevel2;
                }
            }
        }

        return $currentNodeChildren;
	}

	/**
	 * [findSiblings description]
	 * @return Array List of direct siblings NID without current NID
	 */
	public function findSiblings() {
		$tree = $this->getMenuTree();
		$nid = $this->getCurrentNid();

 		$subtrees = array();
        $currentNodeSubtree = array();
        $currentNodeSiblings = array();

        foreach ($tree as $level1) {
            if ($level1->subtree) {
                foreach ($level1->subtree as $level2) {
                    $routeId = $level2->link->getRouteParameters()['node'];
                    if ($routeId != $nid) {
                        $currentNodeSiblings[] = $routeId;
                    }
                }
            }
        }
        return $currentNodeSiblings;
	}



	/**
	* {@inheritdoc}
	*/
	public function build() {

		$menuName = isset($this->configuration['dcos_block_menu_machine_name']) ? $this->configuration['dcos_block_menu_machine_name'] : null;
		$menuViewmode = isset($this->configuration['dcos_block_contenttype_viewmode']) ? $this->configuration['dcos_block_contenttype_viewmode'] : null;


		if ( !isset( $menuName ) || !isset($menuViewmode) ) {
			//FAILFAST
			return array(
				'#markup' => 'Wrong configuration',
				 '#cache' => array(
			        'contexts' => array('url','url.query_args'),
			        'max-age'  => 1000,
			        'tags'     => array('nodes'.implode($nids).$menuName.$menuViewmode)
			       	)
			);

		}

		$node = \Drupal::routeMatch()->getParameter('node');
		if ( $node ) {
	 		$this->currentNid = $node->id();

			$menuTree = $this->getTreeMenu($menuName);
			$this->setMenuTree($menuTree);

			$nids = $this->findChildren();
			if ( count($nids) == 0) {
				$nids = $this->findSiblings();
			}
			$this->setNidsToDisplay($nids);
		}


		$nids = $this->getNidsToDisplay();
	    if ( $nids) {

	    	$block['content'] = '';

			$nodes = Node::loadMultiple($nids);
			$display = node_view_multiple($nodes, $menuViewmode);

			$cacheTags = 'nodes_'.implode($nids).'_'.$menuName.'_'.$menuViewmode;
			
			return array(
				'content' => $display,
			     '#cache' => array(
			        'max-age'  => 1000,
			        'tags'     => array('dcos', $cacheTags)
			       	)
				  );
	    }

	    return $block;
	}






}
