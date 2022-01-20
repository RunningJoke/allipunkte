const UniqIDFactory = function() {
    this.uuidCounter = 0
}

UniqIDFactory.prototype.install = function(Vue) 
{
    var uuid = this.uuidCounter
    Vue.mixin({
        beforeCreate: function() {
          this.uuid = uuid.toString();
          uuid += 1;
        },
      });
    Vue.prototype.$id = function(id) {
        return "uid-" + this.uuid + "-" + id;
      };
}

const UniqIDSingleton = new UniqIDFactory()

export default UniqIDSingleton