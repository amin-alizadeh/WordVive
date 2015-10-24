var push_apply = Function.apply.bind([].push);
var slice_call = Function.call.bind([].slice);

Object.defineProperty(Array.prototype, "pushArrayMembers", {
    value: function() {
        for (var i = 0; i < arguments.length; i++) {
            var to_add = arguments[i];
            for (var n = 0; n < to_add.length; n+=300) {
                push_apply(this, slice_call(to_add, n, n+300));
            }
        }
    }
});
/*
array1.pushArrayMembers(array2, array3);
*/