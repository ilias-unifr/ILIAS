<!doctype html public "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
<title>Yahoo! UI Library - Tree Control</title>
<link rel="stylesheet" type="text/css" href="css/screen.css">
<link rel="stylesheet" type="text/css" href="css/local/tree.css">
</head>
  
<body onload="treeInit()">

<div id="pageTitle">
	<h3>Tree Control</h3>
</div>

<?php include('inc-alljs.php'); ?>
<?php include('inc-rightbar.php'); ?>

  <div id="content">
    <form name="mainForm" action="javscript:;">
	<div class="newsItem">
	  <h3>Default TreeView Widget</h3>
	  <p>
		
	  </p>

	  <div id="expandcontractdiv">
		<a href="javascript:tree.expandAll()">Expand all</a>
		<a href="javascript:tree.collapseAll()">Collapse all</a>
	  </div>
	  <div id="treeDiv1"></div>

	</div>
	</form>
  </div>
	
      <div id="footerContainer">
        <div id="footer">
          <p>&nbsp;</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">

	var tree;
	var nodes = new Array();
	var nodeIndex;
	
	function treeInit() {
		buildRandomTextNodeTree();
	}
	
	function buildRandomTextNodeTree() {
		tree = new YAHOO.widget.TreeView("treeDiv1");
        tree.onExpand = treeExpand;

		for (var i = 0; i < Math.floor((Math.random()*4) + 3); i++) {
			var tmpNode = new YAHOO.widget.TextNode("label-" + i, tree.getRoot(), false);
			// tmpNode.collapse();
			// tmpNode.expand();
			// buildRandomTextBranch(tmpNode);
			buildLargeBranch(tmpNode);
		}

		tree.draw();
	}

	var callback = null;

    function treeExpand(node) {
        var childEl = node.getChildrenEl();
        childEl.style.position= "relative";
        childEl.style.bottom = childEl.offsetHeight + "px";
        childEl.style.border = "1px solid black";
    }

	function buildLargeBranch(node) {
		if (node.depth < 10) {
			YAHOO.log("buildRandomTextBranch: " + node.index);
			for ( var i = 0; i < 10; i++ ) {
				new YAHOO.widget.TextNode(node.label + "-" + i, node, false);
			}
		}
	}

	function buildRandomTextBranch(node) {
		if (node.depth < 10) {
			YAHOO.log("buildRandomTextBranch: " + node.index);
			for ( var i = 0; i < Math.floor(Math.random() * 4) ; i++ ) {
				var tmpNode = new YAHOO.widget.TextNode(node.label + "-" + i, node, false);
				buildRandomTextBranch(tmpNode);
			}
		}
	}

</script>

  </body>
</html>
 
