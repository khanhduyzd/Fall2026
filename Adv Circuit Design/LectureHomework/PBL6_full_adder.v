
module full_adder (
    input  [3:0] a,
    input  [3:0] b,
    output [4:0] c
);

    assign c = {1'b0, a} + {1'b0, b};

endmodule
