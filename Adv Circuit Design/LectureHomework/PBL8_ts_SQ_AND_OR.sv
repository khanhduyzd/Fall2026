
`timescale 1ns/1ps

module tb_seq_and_or;

    reg a, b, c, d;
    reg clk, rst;
    wire f;
    reg expected;
    integer i, errors;

    seq_and_or uut (
        .a(a), .b(b),
        .c(c), .d(d),
        .clk(clk), .rst(rst),
        .f(f)
    );

    initial begin
        clk = 0;
        forever #5 clk = ~clk;
    end

    initial begin
        {a, b, c, d} = 0;
        rst = 0;
        errors = 0;

        #2;
        if (f !== 0)
            errors = errors + 1;

        #10;
        rst = 1;

        for (i = 0; i < 16; i = i + 1) begin
            @(negedge clk);
            {a, b, c, d} = i;
            expected = (a & b) | c | d;

            @(posedge clk);
            #1;

            if (f !== expected) begin
                $display("ERROR: input=%b%b%b%b",
                         a, b, c, d);
                errors = errors + 1;
            end
        end

        if (errors == 0)
            $display("PASS: All 16 combinations passed.");
        else
            $display("FAIL: %0d errors.", errors);

        $finish;
    end
endmodule
