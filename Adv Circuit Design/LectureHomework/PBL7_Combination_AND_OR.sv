
module com_and_or (
    input a,
    input b,
    input c,
    input d,
    output e
);

    wire and_out;
    wire or_out;

    assign and_out = a & b;
    assign or_out = c | d;
    assign e = and_out | or_out;

endmodule
