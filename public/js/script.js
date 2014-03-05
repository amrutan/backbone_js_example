$(function(){
	
	//define a model
	var BookStore = Backbone.Model.extend({
		//set defaults
		urlRoot: "book",
		defaults: {
			book_id: '0',
	        coverImage: 'img/placeholder.png',
	        title: 'No title',
	        author: 'Unknown',
	        releaseDate: 'Unknown',
	        keywords: 'None',
	        idAttribute:"_id",
	        id:null
	    },
	    
	    sync: function(method, model, options) 
	    {
	         options.url = this.build_param(method);
	         return Backbone.sync.apply(this, arguments);
		},
	    
		build_param : function(method)
		{
			var response = {};
			//alert(method)
			switch(method)
			{
				case "delete":
				    return "/book/delete?" + $.param({"book_id" :  this.get("id")});
				   
				case "update":
					that = this;
				     _.extend(response,{"id" : that.get("id")},{"title" : that.get("title")}, {"author" : that.get("author")},{'releaseDate' : that.get("releaseDate")},{'keywords' : that.get("keywords")} );
				    return "/book/put?"+$.param(response);
				    
				case "create":
					that = this;
				     _.extend(response,{"title" : that.get("title")}, {"author" : that.get("author")},{'releaseDate' : that.get("releaseDate")},{'keywords' : that.get("keywords")} );
				    return "/book/post?"+$.param(response);
				    
				case "read":
					return "/book/get?" + $.param({"book_id" :  this.get("id")});
			}		
		}	
    });
	
	//define collection
	var BookStoreList = Backbone.Collection.extend({
	    model: BookStore,
	    url: "book/list"
	});
	
	//define views
	var BooksView = Backbone.View.extend({
		tagName: 'div',
	    className: 'bookContainer',
	    template: _.template( $( '#bookTemplate' ).html() ),
	    
	    events: {
	        'click .delete': 'deleteBook'
	    },
	    
	    render: function() {
	        this.$el.html( this.template( this.model.toJSON()));
	        return this;
	    },
	    
	    deleteBook: function(e){
	    	e.preventDefault();
	    	var id = $(e.currentTarget).data("id");
	    	var flag = confirm('Are you sure you want to delete Book Details?');
	    	if(flag){
	    		var bookObj = new BookStore();
	    		bookObj.set({
	    			"id":id
	    		});	
	    		if(bookObj.destroy()){
	    			this.remove();
	    		}
	        }
	    }
    });
	
	var libraryView = Backbone.View.extend({
		model: BookStore,
	    el: '#books',
	      
	    events: { 'click #add': 'addBook',
	    		  'click .edit': 'editBook'
	    	    },
	    
	    initialize: function() {
	    	this.collection = new BookStoreList();
	        viewObj = this;
	    	var objCollection = new BookStoreList();
	    	this.collection.fetch({}),
	    	
	        this.listenTo( this.collection, 'add', this.renderBook );
	    	this.model.bind("remove", function() {
	    		  this.destroy();
	    	});
	    },

	    // render library by rendering each book in its collection
	    render: function() {
	    	this.$el.find(".bookContainer").remove();
	        this.collection.each(function( item ) {
	            this.renderBook( item );
	        }, this );
	    },

	    //render individual book
	    renderBook: function( item ) {
	        var bookView = new BooksView({
	            model: item
	        });
	        this.$el.append( bookView.render().el );
	    },
	    
	    addBook: function( e ) {
	        e.preventDefault();
	        var that = this;
	        var id = $("#bookId").val();
	        if(id != ""){
	        	var edited = this.collection.get(id);
	        	edited.set({
	        	    "id":$("#bookId").val(),
		            "title":$('#title').val(),
		            "author":$('#author').val(),
		            "releaseDate":$('#releaseDate').val(),
		            "keywords":$('#keywords').val(),
		        });
    			
    			edited.save(null,{
	 	        	success:function(model,response,option){
	 	        		var bookId = response.id;
	 	        		$("#author").val("")
	 	        		$("#title").val("")
	 	        		$("#releaseDate").val("")
	 	        		$("#author").val("")
	 	        		$("#keywords").val("")
	 	        		$("#bookId").val("")
	 	        		that.render();
	 	        	}
	 	        });
	        }else{
	        	var newObj = new BookStore();
	        	newObj.set({
		            "title":$('#title').val(),
		            "author":$('#author').val(),
		            "releaseDate":$('#releaseDate').val(),
		            "keywords":$('#keywords').val(),
		        });
	        	
	        	 newObj.save(null,{
	 	        	success:function(model,response,option){
	 	        		var bookId = response.id;
	 	        		$("#author").val("")
	 	        		$("#title").val("")
	 	        		$("#releaseDate").val("")
	 	        		$("#author").val("")
	 	        		$("#keywords").val("")
	 	        		$("#bookId").val("")
	 	        		newObj.set({
	 		        		id: bookId
	 		        	});
	 	        		that.collection.add(newObj);
	 	        		that.render();
	 	        	}
	 	        });
	        }
	    },
	    
	    editBook: function(e){
	    	e.preventDefault();
	    	var id = $(e.currentTarget).data("id");
	    	var bookObj = new BookStore({id: id});
	    	bookObj.fetch({
	    		success:function(model,response,option){
	    			$("#bookId").val(response[0].id)
	    			$("#author").val(response[0].author)
	    			$("#title").val(response[0].title)
	    			$("#keywords").val(response[0].keywords)
	    			$("#releaseDate").val(response[0].releaseDate.date)
	    		}
	    	});	
	    }
	     
	});
	
	//Define router 
	var Router = Backbone.Router.extend({
	    routes: {
	        '': 'index',
	    },
	    index: function(){
	        //console.log("Index route has been called..");
	    }
	});

	new libraryView();
	new Router;
	Backbone.history.start();
})