(function($){
	
	$.fn.mySort = function(opt){
	
	
		var options = $.extend({
			dinamicArea: 'dinamic area for ajax id'
			}, opt);
		
		
		return this.each(function(){
			
			var self = {};
			var _this = $(this);
			
			
			self.init = function(){
				self.bindEvents();
				}
			
			
			self.bindEvents = function(){
				
					_this.bind('click', function(){
							
							var sortDir = $(this).data('sort');
							var columnName = $(this).data('column');
							if($.trim(sortDir) === '')
								sortDir = 'ASC';
							
							$.ajax({
								data:{
									sort:(sortDir === 'ASC')?'DESC':'ASC',
									column: columnName,
									ajax:'Y'
									},
								success:function(_data){
									$(options.dinamicArea).html(_data);
									}
								});
						});
				};
			
			
			
			self.init();
			});
		
	}
	
	
	
	
	})(jQuery);
